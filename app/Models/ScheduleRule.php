<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * ScheduleRule Model
 * Recurrence rules for automatic generation of hospital inspection schedules.
 */
class ScheduleRule extends BaseModel
{
    protected string $table = 'schedule_rules';

    public const FREQUENCIES = [
        'daily'       => 'Daily (Every Day)',
        'weekdays'    => 'Weekdays (Monday to Friday)',
        'weekly'      => 'Weekly (Selected Days)',
        'shift_based' => 'Shift-Based Roster',
        'monthly'     => 'Monthly',
        'custom'      => 'Custom Days',
    ];

    /**
     * Get all schedule rules with template, shift, facility, and supervisor details
     */
    public function getAllWithDetails(array $filters = [], int $page = 1, int $perPage = 15): array
    {
        $conditions = ["1=1"];
        $params = [];

        if (!empty($filters['status'])) {
            $conditions[] = "sr.status = :status";
            $params['status'] = $filters['status'];
        }

        if (!empty($filters['template_id'])) {
            $conditions[] = "sr.template_id = :template_id";
            $params['template_id'] = (int)$filters['template_id'];
        }

        if (!empty($filters['frequency'])) {
            $conditions[] = "sr.frequency = :frequency";
            $params['frequency'] = $filters['frequency'];
        }

        $whereClause = implode(' AND ', $conditions);
        $offset = ($page - 1) * $perPage;

        $countSql = "SELECT COUNT(*) as total FROM `{$this->table}` sr WHERE {$whereClause}";
        $totalRecords = (int)(Database::selectOne($countSql, $params)['total'] ?? 0);
        $totalPages = (int)ceil($totalRecords / $perPage);

        $sql = "SELECT sr.*,
                       ct.template_name, ct.template_code, ct.category,
                       s.shift_name, s.shift_code, s.start_time as shift_start, s.end_time as shift_end,
                       b.building_name, f.floor_name, w.ward_name, loc.location_name,
                       u.full_name as assigned_user_name,
                       cb.full_name as created_by_name,
                       (SELECT COUNT(*) FROM `inspection_schedules` isc WHERE isc.schedule_rule_id = sr.id) as generated_count
                FROM `{$this->table}` sr
                JOIN `checklist_templates` ct ON sr.template_id = ct.id
                LEFT JOIN `shifts` s ON sr.shift_id = s.id
                LEFT JOIN `buildings` b ON sr.target_building_id = b.id
                LEFT JOIN `floors` f ON sr.target_floor_id = f.id
                LEFT JOIN `wards` w ON sr.target_ward_id = w.id
                LEFT JOIN `inspection_locations` loc ON sr.target_location_id = loc.id
                LEFT JOIN `users` u ON sr.assigned_user_id = u.id
                LEFT JOIN `users` cb ON sr.created_by = cb.id
                WHERE {$whereClause}
                ORDER BY sr.created_at DESC
                LIMIT {$perPage} OFFSET {$offset}";

        $records = Database::selectAll($sql, $params);

        return [
            'data' => $records,
            'pagination' => [
                'total_records' => $totalRecords,
                'total_pages'   => $totalPages,
                'current_page'  => $page,
                'per_page'      => $perPage,
                'has_previous'  => $page > 1,
                'has_next'      => $page < $totalPages,
            ]
        ];
    }
}
