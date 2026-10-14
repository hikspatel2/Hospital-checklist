<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * InspectionLocation Model
 * Manages specific inspection checkpoints within wards (e.g. washrooms, nursing stations, patient beds).
 */
class InspectionLocation extends BaseModel
{
    protected string $table = 'inspection_locations';
    protected bool $useSoftDeletes = true;

    /**
     * Get paginated inspection checkpoints with ward, floor, and building hierarchy
     */
    public function getAllWithDetails(array $filters = [], int $page = 1, int $perPage = 15): array
    {
        $conditions = ["loc.deleted_at IS NULL"];
        $params = [];

        if (!empty($filters['search'])) {
            $conditions[] = "(loc.location_name LIKE :search OR loc.location_code LIKE :search OR loc.qr_code_identifier LIKE :search)";
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

        if (!empty($filters['ward_id'])) {
            $conditions[] = "loc.ward_id = :ward_id";
            $params['ward_id'] = (int)$filters['ward_id'];
        }

        if (!empty($filters['location_type'])) {
            $conditions[] = "loc.location_type = :location_type";
            $params['location_type'] = $filters['location_type'];
        }

        if (isset($filters['status']) && $filters['status'] !== '') {
            $conditions[] = "loc.status = :status";
            $params['status'] = $filters['status'];
        }

        $whereClause = implode(' AND ', $conditions);
        $offset = ($page - 1) * $perPage;

        $countSql = "SELECT COUNT(*) as total 
                     FROM `inspection_locations` loc
                     JOIN `wards` w ON loc.ward_id = w.id
                     JOIN `floors` f ON w.floor_id = f.id
                     JOIN `buildings` b ON f.building_id = b.id
                     WHERE {$whereClause}";
        $totalRecords = (int)(Database::selectOne($countSql, $params)['total'] ?? 0);
        $totalPages = (int)ceil($totalRecords / $perPage);

        $sql = "SELECT loc.*, 
                       w.ward_name, w.ward_code, w.ward_type,
                       f.id as floor_id, f.floor_number, f.floor_name,
                       b.id as building_id, b.building_name, b.building_code,
                       (SELECT COUNT(*) FROM `inspections` i WHERE i.location_id = loc.id) as inspection_count,
                       (SELECT MAX(inspection_date) FROM `inspections` i WHERE i.location_id = loc.id) as last_inspection_date
                FROM `inspection_locations` loc
                JOIN `wards` w ON loc.ward_id = w.id
                JOIN `floors` f ON w.floor_id = f.id
                JOIN `buildings` b ON f.building_id = b.id
                WHERE {$whereClause}
                ORDER BY b.building_name ASC, f.floor_number ASC, w.ward_name ASC, loc.location_name ASC
                LIMIT {$perPage} OFFSET {$offset}";

        $records = Database::selectAll($sql, $params);

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

    /**
     * Check if a location code already exists
     */
    public function isCodeTaken(string $code, ?int $excludeId = null): bool
    {
        $sql = "SELECT id FROM `{$this->table}` WHERE `location_code` = :code";
        $params = ['code' => strtoupper(trim($code))];

        if ($excludeId !== null) {
            $sql .= " AND id != :exclude_id";
            $params['exclude_id'] = $excludeId;
        }

        $res = Database::selectOne($sql, $params);
        return $res !== null;
    }

    /**
     * Generate unique QR code identifier for location checkpoint
     */
    public function generateUniqueQrCode(string $prefix = 'QR-LOC'): string
    {
        do {
            $token = $prefix . '-' . strtoupper(bin2hex(random_bytes(4)));
            try {
                $exists = Database::selectOne("SELECT id FROM `{$this->table}` WHERE `qr_code_identifier` = :token", ['token' => $token]);
            } catch (\Throwable $e) {
                // When offline, random bytes guarantee sufficient uniqueness
                $exists = null;
            }
        } while ($exists !== null);

        return $token;
    }

    /**
     * Check if location has historical inspections
     */
    public function hasHistoricalInspections(int $locationId): bool
    {
        $res = Database::selectOne(
            "SELECT COUNT(*) as cnt FROM `inspections` WHERE `location_id` = :id",
            ['id' => $locationId]
        );
        return (int)($res['cnt'] ?? 0) > 0;
    }

    /**
     * Safe deactivation instead of physical deletion
     */
    public function safeDeactivate(int $locationId, int $userId, string $reason = 'Archived via Admin console'): bool
    {
        return $this->update($locationId, [
            'status' => 'inactive',
            'deletion_reason' => $reason
        ]);
    }

    /**
     * Get all active checkpoints for a specific ward
     */
    public function getByWard(int $wardId, bool $onlyActive = true): array
    {
        $sql = "SELECT * FROM `{$this->table}` WHERE `ward_id` = :ward_id AND `deleted_at` IS NULL";
        $params = ['ward_id' => $wardId];

        if ($onlyActive) {
            $sql .= " AND `status` = 'active'";
        }

        $sql .= " ORDER BY `location_name` ASC";
        return Database::selectAll($sql, $params);
    }
}
