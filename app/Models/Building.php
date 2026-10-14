<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * Building Model
 * Manages hospital blocks, campuses, and multi-floor complexes.
 */
class Building extends BaseModel
{
    protected string $table = 'buildings';
    protected bool $useSoftDeletes = true;

    /**
     * Fetch buildings with associated floor counts and active status
     */
    public function getWithFloorCounts(): array
    {
        $sql = "SELECT b.*, 
                       COUNT(DISTINCT f.id) AS total_configured_floors,
                       COUNT(DISTINCT w.id) AS total_wards,
                       COUNT(DISTINCT sa.id) AS assigned_supervisors_count
                FROM `buildings` b
                LEFT JOIN `floors` f ON b.id = f.building_id AND f.deleted_at IS NULL
                LEFT JOIN `wards` w ON f.id = w.floor_id AND w.deleted_at IS NULL
                LEFT JOIN `supervisor_assignments` sa ON b.id = sa.building_id AND sa.status = 'active'
                WHERE b.deleted_at IS NULL
                GROUP BY b.id
                ORDER BY b.code ASC, b.name ASC";

        return Database::select($sql);
    }

    /**
     * Find a building and eager-load all its active floors and wards
     */
    public function findWithFloors(int $id): ?array
    {
        $building = $this->find($id);
        if (!$building) {
            return null;
        }

        $floorsSql = "SELECT f.*, 
                             COUNT(DISTINCT w.id) AS total_wards,
                             COUNT(DISTINCT fpa.id) AS floor_plan_areas_count
                      FROM `floors` f
                      LEFT JOIN `wards` w ON f.id = w.floor_id AND w.deleted_at IS NULL
                      LEFT JOIN `floor_plan_areas` fpa ON f.id = fpa.floor_id
                      WHERE f.building_id = :building_id 
                        AND f.deleted_at IS NULL
                      GROUP BY f.id
                      ORDER BY f.floor_number ASC";

        $building['floors'] = Database::select($floorsSql, ['building_id' => $id]);
        return $building;
    }

    /**
     * Check if building code is already in use
     */
    public function isCodeTaken(string $code, ?int $excludeId = null): bool
    {
        $sql = "SELECT id FROM `buildings` WHERE `building_code` = :code AND `deleted_at` IS NULL";
        $params = ['code' => strtoupper(trim($code))];

        if ($excludeId !== null) {
            $sql .= " AND `id` != :exclude_id";
            $params['exclude_id'] = $excludeId;
        }

        $sql .= " LIMIT 1";
        return Database::selectOne($sql, $params) !== null;
    }

    /**
     * Check if building has historical inspections preventing safe deletion
     */
    public function hasHistoricalInspections(int $id): bool
    {
        $sql = "SELECT COUNT(*) AS count FROM `inspections` WHERE `building_id` = :id LIMIT 1";
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
     * Check if building has active floors
     */
    public function hasActiveFloors(int $id): bool
    {
        $sql = "SELECT COUNT(*) AS count FROM `floors` WHERE `building_id` = :id AND `status` = 'active' AND `deleted_at` IS NULL LIMIT 1";
        $row = Database::selectOne($sql, ['id' => $id]);
        return (int)($row['count'] ?? 0) > 0;
    }
}

