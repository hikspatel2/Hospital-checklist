<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * Floor Model
 * Manages floor levels, floor plan files, and ward placements.
 */
class Floor extends BaseModel
{
    protected string $table = 'floors';
    protected bool $useSoftDeletes = true;

    /**
     * Get all floors for a given building
     */
    public function getFloorsByBuilding(int $buildingId): array
    {
        $sql = "SELECT f.*, 
                       b.name AS building_name, b.code AS building_code,
                       COUNT(DISTINCT w.id) AS total_wards,
                       COUNT(DISTINCT fpa.id) AS total_areas
                FROM `floors` f
                JOIN `buildings` b ON f.building_id = b.id
                LEFT JOIN `wards` w ON f.id = w.floor_id AND w.deleted_at IS NULL
                LEFT JOIN `floor_plan_areas` fpa ON f.id = fpa.floor_id
                WHERE f.building_id = :building_id 
                  AND f.deleted_at IS NULL
                GROUP BY f.id
                ORDER BY f.floor_number ASC";

        return Database::select($sql, ['building_id' => $buildingId]);
    }

    /**
     * Find floor with building details, wards, and interactive floor plan areas
     */
    public function findWithBuildingAndWards(int $id): ?array
    {
        $sql = "SELECT f.*, 
                       b.name AS building_name, b.code AS building_code, b.status AS building_status
                FROM `floors` f
                JOIN `buildings` b ON f.building_id = b.id
                WHERE f.id = :id AND f.deleted_at IS NULL
                LIMIT 1";

        $floor = Database::selectOne($sql, ['id' => $id]);
        if (!$floor) {
            return null;
        }

        // Fetch wards on this floor
        $wardsSql = "SELECT w.*, 
                            COUNT(DISTINCT il.id) AS total_locations
                     FROM `wards` w
                     LEFT JOIN `inspection_locations` il ON w.id = il.ward_id AND il.deleted_at IS NULL
                     WHERE w.floor_id = :floor_id 
                       AND w.deleted_at IS NULL
                     GROUP BY w.id
                     ORDER BY w.ward_name ASC";
        $floor['wards'] = Database::select($wardsSql, ['floor_id' => $id]);

        // Fetch interactive floor plan areas
        $areasSql = "SELECT fpa.*, w.ward_name, w.ward_code, w.qr_code_identifier
                     FROM `floor_plan_areas` fpa
                     LEFT JOIN `wards` w ON fpa.ward_id = w.id
                     WHERE fpa.floor_id = :floor_id
                     ORDER BY fpa.id ASC";
        $floor['areas'] = Database::select($areasSql, ['floor_id' => $id]);

        return $floor;
    }

    /**
     * Validate uniqueness of floor number within the same building
     */
    public function isFloorNumberTaken(int $buildingId, int $floorNumber, ?int $excludeId = null): bool
    {
        $sql = "SELECT id FROM `floors` WHERE `building_id` = :b_id AND `floor_number` = :f_num AND `deleted_at` IS NULL";
        $params = ['b_id' => $buildingId, 'f_num' => $floorNumber];

        if ($excludeId !== null) {
            $sql .= " AND `id` != :exclude_id";
            $params['exclude_id'] = $excludeId;
        }

        $sql .= " LIMIT 1";
        return Database::selectOne($sql, $params) !== null;
    }

    /**
     * Check if floor has historical inspections
     */
    public function hasHistoricalInspections(int $id): bool
    {
        $sql = "SELECT COUNT(*) AS count FROM `inspections` WHERE `floor_id` = :id LIMIT 1";
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

    /**
     * Check if floor has active wards
     */
    public function hasActiveWards(int $id): bool
    {
        $sql = "SELECT COUNT(*) AS count FROM `wards` WHERE `floor_id` = :id AND `status` = 'active' AND `deleted_at` IS NULL LIMIT 1";
        $row = Database::selectOne($sql, ['id' => $id]);
        return (int)($row['count'] ?? 0) > 0;
    }
}

