<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * UserShiftAssignment Model
 * Manages supervisor shift allocations, location scoping, and overlap detection.
 */
class UserShiftAssignment extends BaseModel
{
    protected string $table = 'user_shift_assignments';
    protected bool $useSoftDeletes = false;

    /**
     * Check if a supervisor has an overlapping shift assignment.
     * Returns true if an active assignment for this user already exists within the date interval.
     */
    public function hasOverlappingAssignment(
        int $userId,
        int $shiftId,
        string $effectiveFrom,
        ?string $effectiveTo,
        ?int $excludeId = null
    ): bool {
        // Date overlap logic:
        // Existing assignment [A_from, A_to] overlaps with [B_from, B_to] if:
        // A_from <= B_to (or B_to is NULL) AND (A_to IS NULL OR A_to >= B_from)
        $sql = "SELECT usa.id, usa.shift_id, s.shift_name, s.start_time, s.end_time
                FROM `user_shift_assignments` usa
                JOIN `shifts` s ON usa.shift_id = s.id
                WHERE usa.user_id = :user_id
                  AND usa.status = 'active'";

        $params = ['user_id' => $userId];

        if ($excludeId !== null) {
            $sql .= " AND usa.id != :exclude_id";
            $params['exclude_id'] = $excludeId;
        }

        // Add date overlap conditions
        if ($effectiveTo !== null) {
            $sql .= " AND usa.effective_from <= :effective_to 
                      AND (usa.effective_to IS NULL OR usa.effective_to >= :effective_from)";
            $params['effective_from'] = $effectiveFrom;
            $params['effective_to'] = $effectiveTo;
        } else {
            // Indefinite target: overlaps with any record whose effective_to is NULL or >= effective_from
            $sql .= " AND (usa.effective_to IS NULL OR usa.effective_to >= :effective_from)";
            $params['effective_from'] = $effectiveFrom;
        }

        $existing = Database::selectAll($sql, $params);
        if (empty($existing)) {
            return false;
        }

        // If target shift is identical, it's definitely an overlap
        foreach ($existing as $row) {
            if ((int)$row['shift_id'] === $shiftId) {
                return true;
            }
        }

        // Also check if shift hours overlap on the same calendar days
        $targetShift = (new Shift())->find($shiftId);
        if (!$targetShift) {
            return false;
        }

        $targetStart = strtotime($targetShift['start_time']);
        $targetEnd = strtotime($targetShift['end_time']);
        if ($targetEnd < $targetStart) {
            $targetEnd += 86400; // overnight adjustment
        }

        foreach ($existing as $row) {
            $existingStart = strtotime($row['start_time']);
            $existingEnd = strtotime($row['end_time']);
            if ($existingEnd < $existingStart) {
                $existingEnd += 86400;
            }

            // Check time overlap: (StartA < EndB) and (EndA > StartB)
            if ($targetStart < $existingEnd && $targetEnd > $existingStart) {
                return true;
            }
        }

        return false;
    }

    /**
     * Create a shift allocation with validation
     */
    public function createAssignment(array $data): int
    {
        $userId = (int)($data['user_id'] ?? 0);
        $shiftId = (int)($data['shift_id'] ?? 0);
        $effectiveFrom = trim((string)($data['effective_from'] ?? date('Y-m-d')));
        $effectiveTo = !empty($data['effective_to']) ? trim((string)$data['effective_to']) : null;
        $buildingId = !empty($data['building_id']) ? (int)$data['building_id'] : null;
        $floorId = !empty($data['floor_id']) ? (int)$data['floor_id'] : null;
        $wardId = !empty($data['ward_id']) ? (int)$data['ward_id'] : null;

        if ($userId <= 0 || $shiftId <= 0) {
            throw new \InvalidArgumentException('Supervisor and Shift selection are required.');
        }

        if ($effectiveTo !== null && $effectiveTo < $effectiveFrom) {
            throw new \InvalidArgumentException('Effective end date cannot precede effective start date.');
        }

        // Overlap verification
        if ($this->hasOverlappingAssignment($userId, $shiftId, $effectiveFrom, $effectiveTo)) {
            throw new \InvalidArgumentException('Supervisor already has an overlapping active shift assignment during this date period.');
        }

        return $this->create([
            'user_id'        => $userId,
            'shift_id'       => $shiftId,
            'building_id'    => $buildingId,
            'floor_id'       => $floorId,
            'ward_id'        => $wardId,
            'effective_from' => $effectiveFrom,
            'effective_to'   => $effectiveTo,
            'status'         => 'active',
        ]);
    }

    /**
     * Get paginated supervisor shift assignments with user, shift, and location details
     */
    public function getAssignmentsWithDetails(array $filters = [], int $page = 1, int $perPage = 15): array
    {
        $conditions = [];
        $params = [];

        if (!empty($filters['user_id'])) {
            $conditions[] = "usa.user_id = :user_id";
            $params['user_id'] = (int)$filters['user_id'];
        }

        if (!empty($filters['shift_id'])) {
            $conditions[] = "usa.shift_id = :shift_id";
            $params['shift_id'] = (int)$filters['shift_id'];
        }

        if (isset($filters['status']) && $filters['status'] !== '') {
            $conditions[] = "usa.status = :status";
            $params['status'] = $filters['status'];
        }

        $whereClause = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';
        $offset = ($page - 1) * $perPage;

        $countSql = "SELECT COUNT(*) as total FROM `user_shift_assignments` usa {$whereClause}";
        $totalRecords = (int)(Database::selectOne($countSql, $params)['total'] ?? 0);
        $totalPages = (int)ceil($totalRecords / $perPage);

        $sql = "SELECT usa.*,
                       u.full_name as supervisor_name, u.employee_code, u.email as supervisor_email,
                       s.shift_code, s.shift_name, s.start_time, s.end_time, s.grace_period_minutes, s.is_overnight,
                       b.building_name, b.building_code,
                       f.floor_name, f.floor_number,
                       w.ward_name, w.ward_code
                FROM `user_shift_assignments` usa
                JOIN `users` u ON usa.user_id = u.id
                JOIN `shifts` s ON usa.shift_id = s.id
                LEFT JOIN `buildings` b ON usa.building_id = b.id
                LEFT JOIN `floors` f ON usa.floor_id = f.id
                LEFT JOIN `wards` w ON usa.ward_id = w.id
                {$whereClause}
                ORDER BY usa.effective_from DESC, usa.created_at DESC
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
     * Get active shift for a supervisor on a given date/time
     */
    public function getActiveShiftForUserOnDate(int $userId, string $date): ?array
    {
        $sql = "SELECT usa.*, s.shift_code, s.shift_name, s.start_time, s.end_time, s.grace_period_minutes, s.is_overnight
                FROM `user_shift_assignments` usa
                JOIN `shifts` s ON usa.shift_id = s.id
                WHERE usa.user_id = :user_id
                  AND usa.status = 'active'
                  AND s.status = 'active'
                  AND usa.effective_from <= :date
                  AND (usa.effective_to IS NULL OR usa.effective_to >= :date)
                LIMIT 1";

        return Database::selectOne($sql, ['user_id' => $userId, 'date' => $date]);
    }
}
