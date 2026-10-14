<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * SupervisorAssignment Model
 * Manages supervisor allocations across buildings, floors, and wards with effective dates.
 */
class SupervisorAssignment extends BaseModel
{
    protected string $table = 'supervisor_assignments';
    protected bool $useSoftDeletes = false; // Status handled via 'active'/'inactive' and revoked_at

    /**
     * Validate facility hierarchy before saving an assignment:
     * - If ward is supplied, it must belong to floor (if supplied).
     * - If floor is supplied, it must belong to building (if supplied).
     * - At least one facility target (building, floor, or ward) must be provided.
     */
    public function validateHierarchy(?int $buildingId, ?int $floorId, ?int $wardId): array
    {
        if ($buildingId === null && $floorId === null && $wardId === null) {
            return ['valid' => false, 'error' => 'You must select at least a Building, Floor, or Ward for assignment.'];
        }

        if ($wardId !== null) {
            $ward = (new Ward())->find($wardId);
            if (!$ward) {
                return ['valid' => false, 'error' => 'Selected ward does not exist.'];
            }

            if ($floorId !== null && (int)$ward['floor_id'] !== $floorId) {
                return ['valid' => false, 'error' => 'The selected Ward does not belong to the selected Floor.'];
            }

            $floor = (new Floor())->find((int)$ward['floor_id']);
            if ($buildingId !== null && (int)$floor['building_id'] !== $buildingId) {
                return ['valid' => false, 'error' => 'The selected Floor/Ward does not belong to the selected Building.'];
            }
        } elseif ($floorId !== null) {
            $floor = (new Floor())->find($floorId);
            if (!$floor) {
                return ['valid' => false, 'error' => 'Selected floor does not exist.'];
            }

            if ($buildingId !== null && (int)$floor['building_id'] !== $buildingId) {
                return ['valid' => false, 'error' => 'The selected Floor does not belong to the selected Building.'];
            }
        } elseif ($buildingId !== null) {
            $building = (new Building())->find($buildingId);
            if (!$building) {
                return ['valid' => false, 'error' => 'Selected building does not exist.'];
            }
        }

        return ['valid' => true, 'error' => null];
    }

    /**
     * Assign a supervisor to a facility level with explicit effective dates
     */
    public function createAssignment(array $data): int
    {
        $userId = (int)($data['user_id'] ?? 0);
        $buildingId = !empty($data['building_id']) ? (int)$data['building_id'] : null;
        $floorId = !empty($data['floor_id']) ? (int)$data['floor_id'] : null;
        $wardId = !empty($data['ward_id']) ? (int)$data['ward_id'] : null;
        $assignedBy = (int)($data['assigned_by'] ?? 0);
        $effectiveFrom = !empty($data['effective_from']) ? trim((string)$data['effective_from']) : date('Y-m-d');
        $effectiveTo = !empty($data['effective_to']) ? trim((string)$data['effective_to']) : null;

        if ($effectiveTo !== null && $effectiveTo < $effectiveFrom) {
            throw new \InvalidArgumentException('Effective end date cannot precede effective start date.');
        }

        $validation = $this->validateHierarchy($buildingId, $floorId, $wardId);
        if (!$validation['valid']) {
            throw new \InvalidArgumentException($validation['error']);
        }

        return $this->create([
            'user_id'        => $userId,
            'building_id'    => $buildingId,
            'floor_id'       => $floorId,
            'ward_id'        => $wardId,
            'assigned_by'    => $assignedBy,
            'status'         => 'active',
            'effective_from' => $effectiveFrom,
            'effective_to'   => $effectiveTo,
            'assigned_at'    => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Revoke an active assignment
     */
    public function revokeAssignment(int $id, int $revokedBy): bool
    {
        return $this->update($id, [
            'status'     => 'inactive',
            'revoked_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Retrieve paginated supervisor assignments with complete user and facility data
     */
    public function getAssignmentsWithDetails(array $filters = [], int $page = 1, int $perPage = 15): array
    {
        $conditions = [];
        $params = [];

        if (!empty($filters['user_id'])) {
            $conditions[] = "sa.user_id = :user_id";
            $params['user_id'] = (int)$filters['user_id'];
        }

        if (!empty($filters['building_id'])) {
            $conditions[] = "sa.building_id = :building_id";
            $params['building_id'] = (int)$filters['building_id'];
        }

        if (!empty($filters['floor_id'])) {
            $conditions[] = "sa.floor_id = :floor_id";
            $params['floor_id'] = (int)$filters['floor_id'];
        }

        if (!empty($filters['ward_id'])) {
            $conditions[] = "sa.ward_id = :ward_id";
            $params['ward_id'] = (int)$filters['ward_id'];
        }

        if (isset($filters['status']) && $filters['status'] !== '') {
            $conditions[] = "sa.status = :status";
            $params['status'] = $filters['status'];
        }

        if (!empty($filters['active_on_date'])) {
            $conditions[] = "sa.status = 'active' AND sa.effective_from <= :active_date AND (sa.effective_to IS NULL OR sa.effective_to >= :active_date)";
            $params['active_date'] = $filters['active_on_date'];
        }

        $whereClause = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';
        $offset = ($page - 1) * $perPage;

        $countSql = "SELECT COUNT(*) as total 
                     FROM `supervisor_assignments` sa
                     JOIN `users` u ON sa.user_id = u.id
                     {$whereClause}";
        $totalRecords = (int)(Database::selectOne($countSql, $params)['total'] ?? 0);
        $totalPages = (int)ceil($totalRecords / $perPage);

        $sql = "SELECT sa.*,
                       u.full_name as supervisor_name, u.employee_code, u.email as supervisor_email,
                       b.building_name, b.building_code,
                       f.floor_number, f.floor_name,
                       w.ward_name, w.ward_code, w.ward_type,
                       assigner.full_name as assigned_by_name
                FROM `supervisor_assignments` sa
                JOIN `users` u ON sa.user_id = u.id
                LEFT JOIN `buildings` b ON sa.building_id = b.id
                LEFT JOIN `floors` f ON sa.floor_id = f.id
                LEFT JOIN `wards` w ON sa.ward_id = w.id
                LEFT JOIN `users` assigner ON sa.assigned_by = assigner.id
                {$whereClause}
                ORDER BY sa.created_at DESC
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
     * Check if a supervisor is currently assigned to a given facility target
     */
    public function isSupervisorAssigned(int $userId, ?int $buildingId, ?int $floorId, ?int $wardId, ?string $date = null): bool
    {
        $checkDate = $date ?? date('Y-m-d');
        $sql = "SELECT id FROM `{$this->table}`
                WHERE `user_id` = :user_id 
                  AND `status` = 'active'
                  AND `effective_from` <= :check_date
                  AND (`effective_to` IS NULL OR `effective_to` >= :check_date)";
        
        $params = [
            'user_id' => $userId,
            'check_date' => $checkDate
        ];

        if ($wardId !== null) {
            $sql .= " AND (`ward_id` = :ward_id OR `floor_id` = :floor_id OR `building_id` = :building_id)";
            $params['ward_id'] = $wardId;
            $params['floor_id'] = $floorId;
            $params['building_id'] = $buildingId;
        } elseif ($floorId !== null) {
            $sql .= " AND (`floor_id` = :floor_id OR `building_id` = :building_id)";
            $params['floor_id'] = $floorId;
            $params['building_id'] = $buildingId;
        } elseif ($buildingId !== null) {
            $sql .= " AND `building_id` = :building_id";
            $params['building_id'] = $buildingId;
        }

        $res = Database::selectOne($sql, $params);
        return $res !== null;
    }
}
