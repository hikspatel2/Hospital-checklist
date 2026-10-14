<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * Shift Model
 * Manages hospital work shifts, overnight shift boundary logic, and grace period calculations.
 */
class Shift extends BaseModel
{
    protected string $table = 'shifts';
    protected bool $useSoftDeletes = true;

    /**
     * Get all shifts with supervisor counts and assignment stats
     */
    public function getAllWithStats(?string $status = null): array
    {
        $sql = "SELECT s.*,
                       (SELECT COUNT(*) FROM `user_shift_assignments` usa WHERE usa.shift_id = s.id AND usa.status = 'active') as active_supervisors_count,
                       (SELECT COUNT(*) FROM `attendance` a WHERE a.shift_id = s.id) as total_attendance_count
                FROM `shifts` s
                WHERE s.deleted_at IS NULL";

        $params = [];
        if ($status !== null) {
            $sql .= " AND s.status = :status";
            $params['status'] = $status;
        }

        $sql .= " ORDER BY s.start_time ASC";
        return Database::selectAll($sql, $params);
    }

    /**
     * Check if a shift code is already in use
     */
    public function isCodeTaken(string $code, ?int $excludeId = null): bool
    {
        $sql = "SELECT id FROM `shifts` WHERE `shift_code` = :code AND `deleted_at` IS NULL";
        $params = ['code' => strtoupper(trim($code))];

        if ($excludeId !== null) {
            $sql .= " AND `id` != :exclude_id";
            $params['exclude_id'] = $excludeId;
        }

        $sql .= " LIMIT 1";
        return Database::selectOne($sql, $params) !== null;
    }

    /**
     * Determine if a shift is an overnight shift crossing midnight.
     * e.g., 22:00:00 to 06:00:00 is an overnight shift because end_time < start_time.
     */
    public static function isOvernightShift(string $startTime, string $endTime): bool
    {
        return strtotime($endTime) < strtotime($startTime);
    }

    /**
     * Check if a specific time falls within the shift bounds,
     * correctly handling overnight shifts crossing midnight.
     */
    public static function isTimeWithinShift(string $timeStr, string $startTime, string $endTime, bool $isOvernight): bool
    {
        $checkTime = date('H:i:s', strtotime($timeStr));
        $start = date('H:i:s', strtotime($startTime));
        $end = date('H:i:s', strtotime($endTime));

        if ($isOvernight) {
            // e.g. 22:00 to 06:00: valid if >= 22:00 OR <= 06:00
            return ($checkTime >= $start || $checkTime <= $end);
        }

        // Standard daytime shift: e.g. 08:00 to 16:00
        return ($checkTime >= $start && $checkTime <= $end);
    }

    /**
     * Check if check-in is within the allowed grace period window.
     */
    public static function isWithinGracePeriod(string $checkInTime, string $startTime, int $graceMinutes = 15): bool
    {
        $checkTs = strtotime(date('H:i:s', strtotime($checkInTime)));
        $startTs = strtotime(date('H:i:s', strtotime($startTime)));
        $graceSec = $graceMinutes * 60;

        // Window: [start_time - 30 mins, start_time + grace_period]
        return ($checkTs >= ($startTs - 1800) && $checkTs <= ($startTs + $graceSec));
    }

    /**
     * Check if shift has historical attendance records preventing deletion
     */
    public function hasAttendanceRecords(int $shiftId): bool
    {
        $row = Database::selectOne(
            "SELECT COUNT(*) as cnt FROM `attendance` WHERE `shift_id` = :id LIMIT 1",
            ['id' => $shiftId]
        );
        return (int)($row['cnt'] ?? 0) > 0;
    }

    /**
     * Safe deactivation preserving all historical rosters
     */
    public function safeDeactivate(int $shiftId, int $userId, string $reason = 'Deactivated via admin console'): bool
    {
        return $this->update($shiftId, [
            'status' => 'inactive',
            'deletion_reason' => $reason
        ]);
    }
}
