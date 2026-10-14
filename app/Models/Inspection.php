<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Models\AuditLog;

/**
 * Inspection Model
 * Core inspection execution records, scoring, server-side validation, and transactional submission.
 */
class Inspection extends BaseModel
{
    protected string $table = 'inspections';

    public const STATUSES = [
        'draft'        => 'In Progress (Draft)',
        'submitted'    => 'Submitted',
        'under_review' => 'Under Review',
        'approved'     => 'Approved',
        'rejected'     => 'Rejected / Re-inspection Required',
    ];

    /**
     * Generate unique sequential inspection reference number
     */
    public static function generateInspectionNumber(): string
    {
        return 'INSP-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
    }

    /**
     * Get inspection with full checklist version, responses, and facility context
     */
    public function getWithDetails(int $inspectionId): ?array
    {
        $sql = "SELECT i.*,
                       ct.template_name, ct.template_code, ct.category,
                       ctv.version_number, ctv.changelog,
                       b.building_name, f.floor_name, f.floor_number, w.ward_name, w.ward_code,
                       loc.location_name, loc.location_code,
                       u.full_name as inspector_name, u.employee_code as inspector_code,
                       s.shift_name, s.shift_code
                FROM `{$this->table}` i
                JOIN `checklist_template_versions` ctv ON i.template_version_id = ctv.id
                JOIN `checklist_templates` ct ON ctv.template_id = ct.id
                JOIN `buildings` b ON i.building_id = b.id
                JOIN `floors` f ON i.floor_id = f.id
                JOIN `wards` w ON i.ward_id = w.id
                LEFT JOIN `inspection_locations` loc ON i.inspection_location_id = loc.id
                JOIN `users` u ON i.inspector_user_id = u.id
                LEFT JOIN `shifts` s ON i.shift_id = s.id
                WHERE i.id = :id AND i.deleted_at IS NULL";

        $inspection = Database::selectOne($sql, ['id' => $inspectionId]);
        if (!$inspection) {
            return null;
        }

        // Fetch responses
        $responsesSql = "SELECT ir.*, ci.item_type, ci.is_required, ci.severity, ci.requires_evidence
                         FROM `inspection_responses` ir
                         JOIN `checklist_items` ci ON ir.checklist_item_id = ci.id
                         WHERE ir.inspection_id = :id
                         ORDER BY ci.display_order ASC, ir.id ASC";
        $inspection['responses'] = Database::selectAll($responsesSql, ['id' => $inspectionId]);

        return $inspection;
    }

    /**
     * Validate that all required items in the template version have valid submitted responses
     */
    public function validateRequiredResponses(int $versionId, array $submittedResponses): array
    {
        $items = Database::selectAll(
            "SELECT id, item_label, item_type, is_required, severity 
             FROM `checklist_items` 
             WHERE `template_version_id` = :vid",
            ['vid' => $versionId]
        );

        $missing = [];
        foreach ($items as $item) {
            $itemId = (int)$item['id'];
            $isRequired = (int)($item['is_required'] ?? 1);

            if ($isRequired === 1) {
                if (!isset($submittedResponses[$itemId]) || trim((string)$submittedResponses[$itemId]) === '') {
                    $missing[] = $item['item_label'];
                }
            }
        }

        return [
            'valid'   => empty($missing),
            'missing' => $missing,
        ];
    }

    /**
     * Transactional Final Inspection Submission
     * Validates required items, calculates compliance score and critical failures,
     * stores response snapshots, updates inspection status, and finalizes schedule.
     */
    public function submitInspection(
        int $inspectionId,
        int $userId,
        array $responses,
        array $remarks = [],
        ?string $overallNotes = null
    ): array {
        $inspection = $this->find($inspectionId);
        if (!$inspection) {
            throw new \InvalidArgumentException('Inspection record not found.');
        }

        if ($inspection['status'] !== 'draft') {
            throw new \InvalidArgumentException('This inspection has already been finalized and submitted.');
        }

        $versionId = (int)$inspection['template_version_id'];

        // 1. Server-side required fields validation
        $valResult = $this->validateRequiredResponses($versionId, $responses);
        if (!$valResult['valid']) {
            throw new \InvalidArgumentException('Required checklist items are unanswered: ' . implode(', ', array_slice($valResult['missing'], 0, 3)));
        }

        // 2. Fetch checklist items to evaluate score and compliance
        $items = Database::selectAll(
            "SELECT * FROM `checklist_items` WHERE `template_version_id` = :vid ORDER BY `display_order` ASC",
            ['vid' => $versionId]
        );

        $totalItems = count($items);
        $passedItems = 0;
        $failedItems = 0;
        $criticalCount = 0;
        $now = date('Y-m-d H:i:s');

        $pdo = Database::getConnection();
        $pdo->beginTransaction();

        try {
            // Delete any existing draft responses for clean insert
            $stmtDel = $pdo->prepare("DELETE FROM `inspection_responses` WHERE `inspection_id` = :id");
            $stmtDel->execute(['id' => $inspectionId]);

            $stmtResp = $pdo->prepare(
                "INSERT INTO `inspection_responses`
                 (`inspection_id`, `checklist_item_id`, `item_label_snapshot`, `response_value`, `score_earned`, `is_compliant`, `is_critical_failure`, `remarks`, `created_at`)
                 VALUES
                 (:insp_id, :item_id, :label, :val, :score, :compliant, :critical, :remarks, :now)"
            );

            foreach ($items as $item) {
                $itemId = (int)$item['id'];
                $val = trim((string)($responses[$itemId] ?? ''));
                $itemRemark = trim((string)($remarks[$itemId] ?? ''));

                $isCompliant = 1;
                $isCriticalFailure = 0;
                $score = 100.00;

                // Evaluate compliance based on type
                if ($item['item_type'] === 'pass_fail') {
                    if (strtolower($val) === 'fail') {
                        $isCompliant = 0;
                        $score = 0.00;
                        $failedItems++;
                        if (($item['severity'] ?? 'medium') === 'critical' || (int)$item['is_critical'] === 1) {
                            $isCriticalFailure = 1;
                            $criticalCount++;
                        }
                    } else {
                        $passedItems++;
                    }
                } elseif ($item['item_type'] === 'yes_no_na') {
                    if (strtolower($val) === 'no') {
                        $isCompliant = 0;
                        $score = 0.00;
                        $failedItems++;
                        if (($item['severity'] ?? 'medium') === 'critical' || (int)$item['is_critical'] === 1) {
                            $isCriticalFailure = 1;
                            $criticalCount++;
                        }
                    } else {
                        $passedItems++;
                    }
                } else {
                    // Numeric or text - compliant if answered
                    $passedItems++;
                }

                $stmtResp->execute([
                    'insp_id'   => $inspectionId,
                    'item_id'   => $itemId,
                    'label'     => $item['item_label'],
                    'val'       => $val,
                    'score'     => $score,
                    'compliant' => $isCompliant,
                    'critical'  => $isCriticalFailure,
                    'remarks'   => !empty($itemRemark) ? $itemRemark : null,
                    'now'       => $now,
                ]);
            }

            $scorePercentage = ($totalItems > 0) ? round(($passedItems / $totalItems) * 100, 2) : 100.00;

            // 3. Update master inspection record
            $stmtInsp = $pdo->prepare(
                "UPDATE `{$this->table}`
                 SET `status` = 'submitted',
                     `score_percentage` = :score,
                     `total_items` = :total,
                     `passed_items` = :passed,
                     `failed_items` = :failed,
                     `critical_issues_count` = :crit,
                     `notes` = :notes,
                     `completed_at` = :now1,
                     `submitted_at` = :now2
                 WHERE `id` = :id"
            );

            $stmtInsp->execute([
                'score'  => $scorePercentage,
                'total'  => $totalItems,
                'passed' => $passedItems,
                'failed' => $failedItems,
                'crit'   => $criticalCount,
                'notes'  => $overallNotes,
                'now1'   => $now,
                'now2'   => $now,
                'id'     => $inspectionId,
            ]);

            // 4. Update linked schedule if present
            if (!empty($inspection['schedule_id'])) {
                $stmtSched = $pdo->prepare("UPDATE `inspection_schedules` SET `status` = 'completed' WHERE `id` = :sid");
                $stmtSched->execute(['sid' => (int)$inspection['schedule_id']]);
            }

            $pdo->commit();

            // 5. Audit Log
            AuditLog::log(
                AuditLog::EVENT_INSPECTION_COMPLETED,
                "Supervisor #{$userId} finalized inspection {$inspection['inspection_number']} ({$scorePercentage}% score, {$criticalCount} critical)",
                $userId,
                [
                    'inspection_id'     => $inspectionId,
                    'inspection_number' => $inspection['inspection_number'],
                    'score_percentage'  => $scorePercentage,
                    'critical_issues'   => $criticalCount
                ]
            );

            return [
                'success'          => true,
                'inspection_id'    => $inspectionId,
                'score_percentage' => $scorePercentage,
                'critical_issues'  => $criticalCount,
            ];
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
