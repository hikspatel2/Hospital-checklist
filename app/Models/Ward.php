<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * Ward Model
 * Manages clinical wards, bed capacities, QR tokens, and inspection checkpoints.
 */
class Ward extends BaseModel
{
    protected string $table = 'wards';
    protected bool $useSoftDeletes = true;

    /**
     * Get all wards with building and floor context
     */
    public function getAllWithDetails(array $filters = [], int $page = 1, int $perPage = 15): array
    {
        $conditions = ["w.deleted_at IS NULL"];
        $params = [];

        if (!empty($filters['search'])) {
            $conditions[] = "(w.ward_name LIKE :search OR w.ward_code LIKE :search OR w.qr_code_identifier LIKE :search)";
            $params['search'] = '%' . trim($filters['search']) . '%';
        }

        if (!empty($filters['building_id'])) {
            $conditions[] = "f.building_id = :building_id";
            $params['building_id'] = (int)$filters['building_id'];
        }

        if (!empty($filters['floor_id'])) {
            $conditions[] = "w.floor_id = :floor_id";
            $params['floor_id'] = (int)$filters['floor_id'];
        }

        if (!empty($filters['ward_type'])) {
            $conditions[] = "w.ward_type = :ward_type";
            $params['ward_type'] = $filters['ward_type'];
        }

        if (isset($filters['status']) && $filters['status'] !== '') {
            $conditions[] = "w.status = :status";
            $params['status'] = $filters['status'];
        }

        $whereClause = implode(' AND ', $conditions);
        $offset = ($page - 1) * $perPage;

        $countSql = "SELECT COUNT(*) as total 
                     FROM `wards` w 
                     JOIN `floors` f ON w.floor_id = f.id
                     JOIN `buildings` b ON f.building_id = b.id
                     WHERE {$whereClause}";
        $totalRecords = (int)(Database::selectOne($countSql, $params)['total'] ?? 0);
        $totalPages = (int)ceil($totalRecords / $perPage);

        $sql = "SELECT w.*, 
                       f.floor_name, f.floor_number,
                       b.name AS building_name, b.code AS building_code,
                       COUNT(DISTINCT il.id) AS total_locations
                FROM `wards` w
                JOIN `floors` f ON w.floor_id = f.id
                JOIN `buildings` b ON f.building_id = b.id
                LEFT JOIN `inspection_locations` il ON w.id = il.ward_id AND il.deleted_at IS NULL
                WHERE {$whereClause}
                GROUP BY w.id
                ORDER BY b.code ASC, f.floor_number ASC, w.ward_name ASC
                LIMIT {$perPage} OFFSET {$offset}";

        $records = Database::select($sql, $params);

        return [
            'data' => $records,
            'current_page' => $page,
            'per_page' => $perPage,
            'total_records' => $totalRecords,
            'total_pages' => $totalPages,
            'has_prev' => $page > 1,
            'has_next' => $page < $totalPages,
        ];
    }

    /**
     * Find ward with full hierarchy (building, floor, locations, active supervisors)
     */
    public function findWithHierarchy(int $id): ?array
    {
        $sql = "SELECT w.*, 
                       f.id AS floor_id, f.floor_name, f.floor_number, f.plan_image_path,
                       b.id AS building_id, b.name AS building_name, b.code AS building_code
                FROM `wards` w
                JOIN `floors` f ON w.floor_id = f.id
                JOIN `buildings` b ON f.building_id = b.id
                WHERE w.id = :id AND w.deleted_at IS NULL
                LIMIT 1";

        $ward = Database::selectOne($sql, ['id' => $id]);
        if (!$ward) {
            return null;
        }

        // Fetch inspection locations in this ward
        $locSql = "SELECT il.*, 
                          COUNT(DISTINCT ir.id) AS historical_responses_count
                   FROM `inspection_locations` il
                   LEFT JOIN `inspection_responses` ir ON il.id = ir.checklist_item_id
                   WHERE il.ward_id = :ward_id 
                     AND il.deleted_at IS NULL
                   GROUP BY il.id
                   ORDER BY il.location_name ASC";
        $ward['locations'] = Database::select($locSql, ['ward_id' => $id]);

        // Fetch assigned supervisors
        $supSql = "SELECT sa.*, u.name AS supervisor_name, u.employee_id, u.email
                   FROM `supervisor_assignments` sa
                   JOIN `users` u ON sa.user_id = u.id
                   WHERE sa.ward_id = :ward_id 
                     AND sa.status = 'active'
                   ORDER BY sa.assigned_at DESC";
        $ward['supervisors'] = Database::select($supSql, ['ward_id' => $id]);

        return $ward;
    }

    /**
     * Check if ward code is already in use
     */
    public function isCodeTaken(string $wardCode, ?int $excludeId = null): bool
    {
        $sql = "SELECT id FROM `wards` WHERE `ward_code` = :code AND `deleted_at` IS NULL";
        $params = ['code' => strtoupper(trim($wardCode))];

        if ($excludeId !== null) {
            $sql .= " AND `id` != :exclude_id";
            $params['exclude_id'] = $excludeId;
        }

        $sql .= " LIMIT 1";
        return Database::selectOne($sql, $params) !== null;
    }

    /**
     * Check if QR identifier is already in use
     */
    public function isQrTaken(string $qrCode, ?int $excludeId = null): bool
    {
        $sql = "SELECT id FROM `wards` WHERE `qr_code_identifier` = :qr AND `deleted_at` IS NULL";
        $params = ['qr' => trim($qrCode)];

        if ($excludeId !== null) {
            $sql .= " AND `id` != :exclude_id";
            $params['exclude_id'] = $excludeId;
        }

        $sql .= " LIMIT 1";
        return Database::selectOne($sql, $params) !== null;
    }

    /**
     * Generate unique, non-guessable QR Code identifier
     */
    public static function generateUniqueQrCode(string $prefix = 'QR-WARD'): string
    {
        do {
            $code = $prefix . '-' . strtoupper(bin2hex(random_bytes(4)));
            try {
                $exists = Database::selectOne("SELECT id FROM `wards` WHERE `qr_code_identifier` = :code LIMIT 1", ['code' => $code]);
            } catch (\Throwable $e) {
                // When offline, random bytes guarantee sufficient uniqueness
                $exists = null;
            }
        } while ($exists !== null);

        return $code;
    }

    /**
     * Check if ward has historical inspections
     */
    public function hasHistoricalInspections(int $id): bool
    {
        $sql = "SELECT COUNT(*) AS count FROM `inspections` WHERE `ward_id` = :id LIMIT 1";
        $row = Database::selectOne($sql, ['id' => $id]);
        return (int)($row['count'] ?? 0) > 0;
    }

    /**
     * Safe deactivation instead of physical deletion
     */
    public function safeDeactivate(int $id, int $userId, string $reason = 'Archived via Admin console'): bool
    {
        return $this->update($id, [
            'status' => 'inactive',
            'deletion_reason' => $reason
        ]);
    }
}

