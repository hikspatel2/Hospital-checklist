<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * InspectionSchedule Model
 * Concrete scheduled hospital inspection instances generated from recurrence rules.
 */
class InspectionSchedule extends BaseModel
{
    protected string $table = 'inspection_schedules';

    public const STATUSES = [
        'scheduled'   => 'Scheduled',
        'pending'     => 'Pending Inspection',
        'in_progress' => 'In Progress',
        'completed'   => 'Completed',
        'overdue'     => 'Overdue',
        'missed'      => 'Missed',
        'cancelled'   => 'Cancelled',
    ];

    public const STATUS_BADGES = [
        'scheduled'   => 'bg-info bg-opacity-25 text-info border border-info border-opacity-25',
        'pending'     => 'bg-primary bg-opacity-25 text-primary border border-primary border-opacity-25',
        'in_progress' => 'bg-warning bg-opacity-25 text-warning border border-warning border-opacity-25',
        'completed'   => 'bg-success bg-opacity-25 text-success border border-success border-opacity-25',
        'overdue'     => 'bg-danger bg-opacity-25 text-danger border border-danger border-opacity-25',
        'missed'      => 'bg-secondary bg-opacity-25 text-secondary border border-secondary border-opacity-25',
        'cancelled'   => 'bg-dark bg-opacity-25 text-dark border border-dark border-opacity-25',
    ];

    /**
     * Get paginated schedule instances with template, ward, location, shift, and assigned supervisor
     */
    public function getAllWithDetails(array $filters = [], int $page = 1, int $perPage = 15): array
    {
        $conditions = ["1=1"];
        $params = [];

        if (!empty($filters['status'])) {
            $conditions[] = "isc.status = :status";
            $params['status'] = $filters['status'];
        }

        if (!empty($filters['date'])) {
            $conditions[] = "isc.scheduled_date = :date";
            $params['date'] = $filters['date'];
        }

        if (!empty($filters['assigned_user_id'])) {
            $conditions[] = "isc.assigned_user_id = :assigned_user_id";
            $params['assigned_user_id'] = (int)$filters['assigned_user_id'];
        }

        if (!empty($filters['ward_id'])) {
            $conditions[] = "isc.ward_id = :ward_id";
            $params['ward_id'] = (int)$filters['ward_id'];
        }

        if (!empty($filters['shift_id'])) {
            $conditions[] = "isc.shift_id = :shift_id";
            $params['shift_id'] = (int)$filters['shift_id'];
        }

        $whereClause = implode(' AND ', $conditions);
        $offset = ($page - 1) * $perPage;

        $countSql = "SELECT COUNT(*) as total FROM `{$this->table}` isc WHERE {$whereClause}";
        $totalRecords = (int)(Database::selectOne($countSql, $params)['total'] ?? 0);
        $totalPages = (int)ceil($totalRecords / $perPage);

        $sql = "SELECT isc.*,
                       ct.template_name, ct.template_code, ct.category,
                       ctv.version_number,
                       w.ward_name, w.ward_code,
                       loc.location_name, loc.location_code,
                       s.shift_name, s.shift_code, s.start_time as shift_start, s.end_time as shift_end,
                       u.full_name as assigned_supervisor_name, u.employee_code,
                       insp.id as inspection_id, insp.status as inspection_status, insp.score_percentage
                FROM `{$this->table}` isc
                JOIN `checklist_template_versions` ctv ON isc.template_version_id = ctv.id
                JOIN `checklist_templates` ct ON ctv.template_id = ct.id
                JOIN `wards` w ON isc.ward_id = w.id
                LEFT JOIN `inspection_locations` loc ON isc.inspection_location_id = loc.id
                LEFT JOIN `shifts` s ON isc.shift_id = s.id
                LEFT JOIN `users` u ON isc.assigned_user_id = u.id
                LEFT JOIN `inspections` insp ON insp.schedule_id = isc.id
                WHERE {$whereClause}
                ORDER BY isc.scheduled_date DESC, isc.scheduled_time_window_start ASC, isc.id DESC
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

    /**
     * Get inspection schedules assigned to a specific supervisor for today or specified date
     */
    public function getSupervisorSchedules(int $userId, string $date): array
    {
        $sql = "SELECT isc.*,
                       ct.template_name, ct.template_code, ct.category,
                       ctv.version_number,
                       w.ward_name, w.ward_code,
                       loc.location_name, loc.location_code,
                       s.shift_name, s.shift_code, s.start_time as shift_start, s.end_time as shift_end,
                       insp.id as inspection_id, insp.status as inspection_status
                FROM `{$this->table}` isc
                JOIN `checklist_template_versions` ctv ON isc.template_version_id = ctv.id
                JOIN `checklist_templates` ct ON ctv.template_id = ct.id
                JOIN `wards` w ON isc.ward_id = w.id
                LEFT JOIN `inspection_locations` loc ON isc.inspection_location_id = loc.id
                LEFT JOIN `shifts` s ON isc.shift_id = s.id
                LEFT JOIN `inspections` insp ON insp.schedule_id = isc.id
                WHERE (isc.assigned_user_id = :uid OR isc.assigned_user_id IS NULL)
                  AND isc.scheduled_date = :date
                  AND isc.status NOT IN ('cancelled', 'completed')
                ORDER BY isc.scheduled_time_window_start ASC, isc.id ASC";

        return Database::selectAll($sql, ['uid' => $userId, 'date' => $date]);
    }
}
