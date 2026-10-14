<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * User Model
 * Manages hospital employee accounts, roles, lockouts, and assignments.
 */
class User extends BaseModel
{
    protected string $table = 'users';
    protected bool $useSoftDeletes = true;

    /**
     * Find user by either email or employee ID
     */
    public function findByIdentity(string $identity): ?array
    {
        $sql = "SELECT * FROM `users` 
                WHERE (`email` = :identity1 OR `employee_id` = :identity2) 
                  AND `deleted_at` IS NULL 
                LIMIT 1";

        return Database::selectOne($sql, [
            'identity1' => $identity,
            'identity2' => $identity,
        ]);
    }

    /**
     * Find active, non-deleted user by primary key
     */
    public function findActiveById(int $id): ?array
    {
        $sql = "SELECT * FROM `users` WHERE `id` = :id AND `deleted_at` IS NULL AND `is_active` = 1 LIMIT 1";
        return Database::selectOne($sql, ['id' => $id]);
    }

    /**
     * Update failed attempts and optional lockout expiration
     */
    public function recordFailedLogin(int $id, int $attempts, ?string $lockUntil): void
    {
        $sql = "UPDATE `users` 
                SET `failed_login_attempts` = :attempts, 
                    `locked_until` = :locked_until 
                WHERE `id` = :id";

        Database::executeStatement($sql, [
            'id' => $id,
            'attempts' => $attempts,
            'locked_until' => $lockUntil,
        ]);
    }

    /**
     * Clear failed login attempts and update login telemetry
     */
    public function recordSuccessfulLogin(int $id, string $ip): void
    {
        $sql = "UPDATE `users` 
                SET `failed_login_attempts` = 0, 
                    `locked_until` = NULL, 
                    `last_login_at` = NOW(), 
                    `last_login_ip` = :ip 
                WHERE `id` = :id";

        Database::executeStatement($sql, [
            'id' => $id,
            'ip' => $ip !== '' ? $ip : ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'),
        ]);
    }

    /**
     * Reset lockout timestamp
     */
    public function resetLockout(int $id): void
    {
        $sql = "UPDATE `users` SET `failed_login_attempts` = 0, `locked_until` = NULL WHERE `id` = :id";
        Database::executeStatement($sql, ['id' => $id]);
    }

    /**
     * Update user password hash
     */
    public function updatePassword(int $id, string $newPasswordHash): void
    {
        $sql = "UPDATE `users` SET `password_hash` = :hash, `updated_at` = NOW() WHERE `id` = :id";
        Database::executeStatement($sql, [
            'id' => $id,
            'hash' => $newPasswordHash,
        ]);
    }

    /**
     * Fetch active facility assignments for a supervisor
     */
    public function getSupervisorAssignments(int $supervisorId): array
    {
        $sql = "SELECT sa.*, 
                       b.name AS building_name, b.code AS building_code,
                       f.floor_name, f.floor_number,
                       w.ward_name, w.ward_code, w.ward_type, w.qr_code_identifier
                FROM `supervisor_assignments` sa
                LEFT JOIN `buildings` b ON sa.building_id = b.id
                LEFT JOIN `floors` f ON sa.floor_id = f.id
                LEFT JOIN `wards` w ON sa.ward_id = w.id
                WHERE sa.user_id = :user_id 
                  AND sa.status = 'active'
                ORDER BY b.name ASC, f.floor_number ASC, w.ward_name ASC";

        return Database::select($sql, ['user_id' => $supervisorId]);
    }

    /**
     * Fetch current active shift for a supervisor
     */
    public function getActiveShift(int $supervisorId): ?array
    {
        $sql = "SELECT usa.*, s.shift_name, s.shift_code, s.start_time, s.end_time, s.grace_period_minutes
                FROM `user_shift_assignments` usa
                JOIN `shifts` s ON usa.shift_id = s.id
                WHERE usa.user_id = :user_id 
                  AND usa.status = 'active'
                  AND CURDATE() >= usa.effective_from 
                  AND (usa.effective_to IS NULL OR CURDATE() <= usa.effective_to)
                LIMIT 1";

        return Database::selectOne($sql, ['user_id' => $supervisorId]);
    }

    /**
     * Filterable list of all employees for Admin management
     */
    public function getFilteredUsers(array $filters = [], int $page = 1, int $perPage = 15): array
    {
        $conditions = ["`deleted_at` IS NULL"];
        $params = [];

        if (!empty($filters['search'])) {
            $conditions[] = "(`name` LIKE :search OR `email` LIKE :search OR `employee_id` LIKE :search OR `designation` LIKE :search)";
            $params['search'] = '%' . trim($filters['search']) . '%';
        }

        if (!empty($filters['role']) && in_array($filters['role'], ['admin', 'supervisor'], true)) {
            $conditions[] = "`role` = :role";
            $params['role'] = $filters['role'];
        }

        if (isset($filters['status']) && $filters['status'] !== '') {
            $conditions[] = "`is_active` = :status";
            $params['status'] = (int)$filters['status'];
        }

        $where = implode(' AND ', $conditions);

        return $this->paginate($page, $perPage, $where, $params, 'id DESC');
    }

    /**
     * Check if email is already taken by another user
     */
    public function isEmailTaken(string $email, ?int $excludeUserId = null): bool
    {
        $sql = "SELECT id FROM `users` WHERE `email` = :email AND `deleted_at` IS NULL";
        $params = ['email' => $email];

        if ($excludeUserId !== null) {
            $sql .= " AND `id` != :exclude_id";
            $params['exclude_id'] = $excludeUserId;
        }

        $sql .= " LIMIT 1";
        return Database::selectOne($sql, $params) !== null;
    }

    /**
     * Check if employee ID is already taken by another user
     */
    public function isEmployeeIdTaken(string $employeeId, ?int $excludeUserId = null): bool
    {
        $sql = "SELECT id FROM `users` WHERE `employee_id` = :emp_id AND `deleted_at` IS NULL";
        $params = ['emp_id' => $employeeId];

        if ($excludeUserId !== null) {
            $sql .= " AND `id` != :exclude_id";
            $params['exclude_id'] = $excludeUserId;
        }

        $sql .= " LIMIT 1";
        return Database::selectOne($sql, $params) !== null;
    }
}
