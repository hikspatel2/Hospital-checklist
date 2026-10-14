<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * FloorPlanArea Model
 * Manages interactive vector/polygon map areas, room zoning, and real-time inspection status overlay.
 */
class FloorPlanArea extends BaseModel
{
    protected string $table = 'floor_plan_areas';
    protected bool $useSoftDeletes = false; // Floor plan zones are physical structural boundaries

    /**
     * Standard status color mapping for visual feedback on floor plans
     */
    public const STATUS_COLORS = [
        'compliant' => '#198754', // Emerald green
        'warning'   => '#ffc107', // Amber
        'critical'  => '#dc3545', // Crimson red
        'pending'   => '#0f2c59', // LG Hospital Navy Blue
        'inactive'  => '#6c757d', // Slate grey
    ];

    /**
     * Get all floor plan areas for a floor with ward context
     */
    public function getByFloor(int $floorId): array
    {
        $sql = "SELECT fpa.*, 
                       w.ward_name, w.ward_code, w.ward_type, w.bed_capacity, w.status as ward_status,
                       (SELECT COUNT(*) FROM `inspection_locations` loc WHERE loc.ward_id = w.id AND loc.status = 'active') as checkpoint_count,
                       (SELECT COUNT(*) FROM `complaints` c WHERE c.ward_id = w.id AND c.status IN ('registered', 'assigned', 'in_progress')) as active_complaints_count
                FROM `floor_plan_areas` fpa
                LEFT JOIN `wards` w ON fpa.ward_id = w.id
                WHERE fpa.floor_id = :floor_id
                ORDER BY fpa.area_label ASC";

        return Database::selectAll($sql, ['floor_id' => $floorId]);
    }

    /**
     * Get inspection status aggregate summary for a floor
     */
    public function getSummaryByFloor(int $floorId): array
    {
        $sql = "SELECT 
                    COUNT(*) as total_areas,
                    SUM(CASE WHEN inspection_status = 'compliant' THEN 1 ELSE 0 END) as compliant_count,
                    SUM(CASE WHEN inspection_status = 'warning' THEN 1 ELSE 0 END) as warning_count,
                    SUM(CASE WHEN inspection_status = 'critical' THEN 1 ELSE 0 END) as critical_count,
                    SUM(CASE WHEN inspection_status = 'pending' THEN 1 ELSE 0 END) as pending_count,
                    SUM(CASE WHEN inspection_status = 'inactive' THEN 1 ELSE 0 END) as inactive_count
                FROM `floor_plan_areas`
                WHERE `floor_id` = :floor_id";

        $row = Database::selectOne($sql, ['floor_id' => $floorId]);
        if (!$row) {
            return [
                'total_areas' => 0,
                'compliant_count' => 0,
                'warning_count' => 0,
                'critical_count' => 0,
                'pending_count' => 0,
                'inactive_count' => 0,
            ];
        }

        return [
            'total_areas'     => (int)($row['total_areas'] ?? 0),
            'compliant_count' => (int)($row['compliant_count'] ?? 0),
            'warning_count'   => (int)($row['warning_count'] ?? 0),
            'critical_count'  => (int)($row['critical_count'] ?? 0),
            'pending_count'   => (int)($row['pending_count'] ?? 0),
            'inactive_count'  => (int)($row['inactive_count'] ?? 0),
        ];
    }

    /**
     * Find single area with full facility hierarchy
     */
    public function findWithDetails(int $id): ?array
    {
        $sql = "SELECT fpa.*, 
                       f.floor_number, f.floor_name, f.plan_image_path,
                       b.id as building_id, b.building_name, b.building_code,
                       w.ward_name, w.ward_code, w.ward_type, w.bed_capacity
                FROM `floor_plan_areas` fpa
                JOIN `floors` f ON fpa.floor_id = f.id
                JOIN `buildings` b ON f.building_id = b.id
                LEFT JOIN `wards` w ON fpa.ward_id = w.id
                WHERE fpa.id = :id";

        return Database::selectOne($sql, ['id' => $id]);
    }

    /**
     * Update inspection status and color
     */
    public function updateInspectionStatus(int $id, string $status, ?string $colorCode = null): bool
    {
        $validStatuses = ['compliant', 'warning', 'critical', 'pending', 'inactive'];
        if (!in_array($status, $validStatuses, true)) {
            throw new \InvalidArgumentException("Invalid inspection status: {$status}");
        }

        $color = $colorCode ?? (self::STATUS_COLORS[$status] ?? '#0f2c59');

        return $this->update($id, [
            'inspection_status' => $status,
            'color_code' => $color
        ]);
    }

    /**
     * Get areas for a ward
     */
    public function getByWard(int $wardId): array
    {
        return Database::selectAll(
            "SELECT * FROM `{$this->table}` WHERE `ward_id` = :ward_id ORDER BY `area_label` ASC",
            ['ward_id' => $wardId]
        );
    }
}
