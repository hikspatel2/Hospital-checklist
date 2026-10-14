<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\AuditLog;

/**
 * ChecklistVersionService
 * Enforces immutable template version snapshots. Never modifies the meaning of
 * a checklist already used in completed or in-progress hospital inspections.
 */
class ChecklistVersionService
{
    /**
     * Determine if a checklist template version has already been used in any inspection.
     * If used, items MUST NEVER be edited in-place; a new version must be branched.
     */
    public static function hasVersionBeenUsed(int $versionId): bool
    {
        try {
            $row = Database::selectOne(
                "SELECT COUNT(*) as cnt FROM `inspections` WHERE `template_version_id` = :vid",
                ['vid' => $versionId]
            );
            return ((int)($row['cnt'] ?? 0)) > 0;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Retrieve the current active version for a checklist template
     */
    public static function getCurrentVersion(int $templateId): ?array
    {
        try {
            return Database::selectOne(
                "SELECT * FROM `checklist_template_versions` 
                 WHERE `template_id` = :tid AND `is_current` = 1 
                 ORDER BY `version_number` DESC LIMIT 1",
                ['tid' => $templateId]
            );
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Get all version snapshots for a template with inspection counts
     */
    public static function getVersionHistory(int $templateId): array
    {
        try {
            $sql = "SELECT ctv.*, 
                           u.full_name as created_by_name,
                           (SELECT COUNT(*) FROM `inspections` i WHERE i.template_version_id = ctv.id) as inspection_count,
                           (SELECT COUNT(*) FROM `checklist_items` ci WHERE ci.template_version_id = ctv.id) as item_count
                    FROM `checklist_template_versions` ctv
                    LEFT JOIN `users` u ON ctv.created_by = u.id
                    WHERE ctv.template_id = :tid
                    ORDER BY ctv.version_number DESC";
            return Database::selectAll($sql, ['tid' => $templateId]);
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Create the initial Version 1 for a newly created template
     */
    public static function createInitialVersion(int $templateId, int $createdBy): int
    {
        $sql = "INSERT INTO `checklist_template_versions` 
                (`template_id`, `version_number`, `changelog`, `is_current`, `published_at`, `created_by`, `created_at`)
                VALUES (:tid, 1, 'Initial template baseline creation', 1, NOW(), :uid, NOW())";
        
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'tid' => $templateId,
            'uid' => $createdBy,
        ]);

        return (int)$pdo->lastInsertId();
    }

    /**
     * Branch/clone a new version when editing a template that has already been used in inspections.
     * Clones all items from current version to the new version number.
     */
    public static function branchNewVersion(int $templateId, int $userId, string $changelog = ''): int
    {
        $current = self::getCurrentVersion($templateId);
        $nextVersionNum = $current ? ((int)$current['version_number'] + 1) : 1;
        $prevVersionId = $current ? (int)$current['id'] : 0;

        $pdo = Database::getConnection();
        $pdo->beginTransaction();

        try {
            // 1. Mark previous versions as not current
            $stmt = $pdo->prepare("UPDATE `checklist_template_versions` SET `is_current` = 0 WHERE `template_id` = :tid");
            $stmt->execute(['tid' => $templateId]);

            // 2. Insert new version record
            $insertSql = "INSERT INTO `checklist_template_versions` 
                          (`template_id`, `version_number`, `changelog`, `is_current`, `published_at`, `created_by`, `created_at`)
                          VALUES (:tid, :vnum, :log, 1, NOW(), :uid, NOW())";
            $stmt = $pdo->prepare($insertSql);
            $stmt->execute([
                'tid'  => $templateId,
                'vnum' => $nextVersionNum,
                'log'  => !empty($changelog) ? $changelog : "Branched version {$nextVersionNum} to preserve historical inspection snapshots",
                'uid'  => $userId,
            ]);
            $newVersionId = (int)$pdo->lastInsertId();

            // 3. Clone all items from previous version if present
            if ($prevVersionId > 0) {
                $itemsStmt = $pdo->prepare("SELECT * FROM `checklist_items` WHERE `template_version_id` = :pvid ORDER BY `display_order` ASC");
                $itemsStmt->execute(['pvid' => $prevVersionId]);
                $items = $itemsStmt->fetchAll(\PDO::FETCH_ASSOC);

                $cloneStmt = $pdo->prepare(
                    "INSERT INTO `checklist_items`
                     (`template_version_id`, `item_label`, `description`, `item_type`, `options_json`, `requires_evidence`, `is_critical`, `is_required`, `severity`, `display_order`, `created_at`)
                     VALUES (:vid, :label, :desc, :type, :opt, :ev, :crit, :req, :sev, :ord, NOW())"
                );

                foreach ($items as $item) {
                    $cloneStmt->execute([
                        'vid'   => $newVersionId,
                        'label' => $item['item_label'],
                        'desc'  => $item['description'],
                        'type'  => $item['item_type'],
                        'opt'   => $item['options_json'],
                        'ev'    => $item['requires_evidence'],
                        'crit'  => $item['is_critical'],
                        'req'   => $item['is_required'] ?? 1,
                        'sev'   => $item['severity'] ?? 'medium',
                        'ord'   => $item['display_order'],
                    ]);
                }
            }

            $pdo->commit();

            AuditLog::log(
                AuditLog::EVENT_FACILITY_UPDATED,
                "Branched checklist template #{$templateId} to Version {$nextVersionNum} (Historical snapshots preserved)",
                $userId,
                ['template_id' => $templateId, 'new_version_id' => $newVersionId, 'version_number' => $nextVersionNum]
            );

            return $newVersionId;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
