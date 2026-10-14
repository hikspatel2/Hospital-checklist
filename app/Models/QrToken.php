<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * QrToken Model
 * Tracks issued, active, expired, and revoked QR tokens across the hospital campus.
 */
class QrToken extends BaseModel
{
    protected string $table = 'qr_tokens';
    protected bool $useSoftDeletes = false; // Revocation handled via is_revoked, revoked_at, revoked_by

    /**
     * Find active QR token for an entity
     */
    public function findActiveByEntity(string $entityType, int $entityId, string $tokenType): ?array
    {
        $sql = "SELECT * FROM `qr_tokens`
                WHERE `entity_type` = :entity_type
                  AND `entity_id` = :entity_id
                  AND `token_type` = :token_type
                  AND `is_revoked` = 0
                  AND (`expires_at` IS NULL OR `expires_at` > NOW())
                ORDER BY `created_at` DESC
                LIMIT 1";

        return Database::selectOne($sql, [
            'entity_type' => $entityType,
            'entity_id'   => $entityId,
            'token_type'  => $tokenType,
        ]);
    }

    /**
     * Get paginated QR tokens with entity details (ward name, location name, creator name)
     */
    public function getAllWithDetails(array $filters = [], int $page = 1, int $perPage = 15): array
    {
        $conditions = [];
        $params = [];

        if (!empty($filters['token_type'])) {
            $conditions[] = "qt.token_type = :token_type";
            $params['token_type'] = $filters['token_type'];
        }

        if (!empty($filters['entity_type'])) {
            $conditions[] = "qt.entity_type = :entity_type";
            $params['entity_type'] = $filters['entity_type'];
        }

        if (isset($filters['is_revoked']) && $filters['is_revoked'] !== '') {
            $conditions[] = "qt.is_revoked = :is_revoked";
            $params['is_revoked'] = (int)$filters['is_revoked'];
        }

        $whereClause = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';
        $offset = ($page - 1) * $perPage;

        $countSql = "SELECT COUNT(*) as total FROM `qr_tokens` qt {$whereClause}";
        $totalRecords = (int)(Database::selectOne($countSql, $params)['total'] ?? 0);
        $totalPages = (int)ceil($totalRecords / $perPage);

        $sql = "SELECT qt.*,
                       creator.full_name as created_by_name,
                       revoker.full_name as revoked_by_name
                FROM `qr_tokens` qt
                LEFT JOIN `users` creator ON qt.created_by = creator.id
                LEFT JOIN `users` revoker ON qt.revoked_by = revoker.id
                {$whereClause}
                ORDER BY qt.created_at DESC
                LIMIT {$perPage} OFFSET {$offset}";

        $records = Database::selectAll($sql, $params);

        // Enrich records with target entity name (Ward / Location)
        foreach ($records as &$rec) {
            $rec['entity_name'] = 'N/A';
            if ($rec['entity_type'] === 'ward') {
                $w = (new Ward())->find((int)$rec['entity_id']);
                $rec['entity_name'] = $w ? "Ward: {$w['ward_name']} ({$w['ward_code']})" : "Ward #{$rec['entity_id']}";
            } elseif ($rec['entity_type'] === 'location') {
                $loc = (new InspectionLocation())->find((int)$rec['entity_id']);
                $rec['entity_name'] = $loc ? "Checkpoint: {$loc['location_name']} ({$loc['location_code']})" : "Location #{$rec['entity_id']}";
            }
        }

        return [
            'data' => $records,
            'pagination' => [
                'total_records' => $totalRecords,
                'total_pages' => $totalPages,
                'current_page' => $page,
                'per_page' => $perPage,
                'has_previous' => $page > 1,
                'has_next' => $page < $totalPages,
            ]
        ];
    }
}
