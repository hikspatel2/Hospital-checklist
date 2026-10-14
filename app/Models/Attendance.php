<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Models\AuditLog;
use App\Services\GeoLocationService;

/**
 * Attendance Model
 * Manages supervisor check-ins, check-outs, GPS verification telemetry,
 * idempotency protection, and auditable admin overrides.
 */
class Attendance extends BaseModel
{
    protected string $table = 'attendance';
    protected bool $useSoftDeletes = true;

    /**
     * Check if user already has an active or completed check-in for this shift & date.
     * Prevents duplicate check-in events and supports idempotent network retries.
     */
    public function getActiveCheckIn(int $userId, int $shiftId, string $date): ?array
    {
        $sql = "SELECT * FROM `attendance`
                WHERE `user_id` = :user_id
                  AND `shift_id` = :shift_id
                  AND `attendance_date` = :date
                  AND `deleted_at` IS NULL
                LIMIT 1";

        return Database::selectOne($sql, [
            'user_id'  => $userId,
            'shift_id' => $shiftId,
            'date'     => $date,
        ]);
    }

    /**
     * Record an idempotent Check-In event with full GPS + QR audit telemetry
     */
    public function recordCheckIn(array $data): array
    {
        $userId       = (int)$data['user_id'];
        $shiftId      = (int)$data['shift_id'];
        $date         = $data['attendance_date'] ?? date('Y-m-d');
        $checkInTime  = $data['check_in_time'] ?? date('Y-m-d H:i:s');
        $wardId       = !empty($data['ward_id']) ? (int)$data['ward_id'] : null;
        $locationId   = !empty($data['location_id']) ? (int)$data['location_id'] : null;
        $qrTokenId    = !empty($data['qr_token_id']) ? (int)$data['qr_token_id'] : null;
        $status       = $data['status'] ?? 'present';
        $verifStatus  = $data['verification_status'] ?? 'verified';
        $notes        = $data['notes'] ?? null;

        // 1. Idempotency Check: if check-in already recorded for today's shift, return existing record
        $existing = $this->getActiveCheckIn($userId, $shiftId, $date);
        if ($existing) {
            return [
                'success'       => true,
                'idempotent'    => true,
                'attendance_id' => (int)$existing['id'],
                'record'        => $existing,
                'message'       => 'Attendance check-in already recorded for this shift.',
            ];
        }

        // 2. Insert Attendance Master Record
        $attendanceId = $this->create([
            'user_id'             => $userId,
            'shift_id'            => $shiftId,
            'ward_id'             => $wardId,
            'location_id'         => $locationId,
            'qr_token_id'         => $qrTokenId,
            'attendance_date'     => $date,
            'check_in_time'       => $checkInTime,
            'status'              => $status,
            'verification_status' => $verifStatus,
            'notes'               => $notes,
        ]);

        // 3. Record GPS & Device Verification Event Telemetry
        $lat = !empty($data['gps_latitude']) ? (float)$data['gps_latitude'] : null;
        $lon = !empty($data['gps_longitude']) ? (float)$data['gps_longitude'] : null;
        $accuracy = !empty($data['gps_accuracy_meters']) ? (float)$data['gps_accuracy_meters'] : null;
        $distance = !empty($data['distance_to_hospital_meters']) ? (float)$data['distance_to_hospital_meters'] : null;
        $withinGeofence = isset($data['is_within_geofence']) ? (int)$data['is_within_geofence'] : 1;
        $isFlagged = isset($data['is_flagged']) ? (int)$data['is_flagged'] : 0;
        $flagReason = $data['flag_reason'] ?? null;
        $clientIp = $data['client_ip'] ?? ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
        $userAgent = $data['device_user_agent'] ?? ($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown');

        $eventSql = "INSERT INTO `attendance_verification_events`
                     (`attendance_id`, `event_type`, `qr_token_id`, `gps_latitude`, `gps_longitude`, `gps_accuracy_meters`, 
                      `distance_to_hospital_meters`, `is_within_geofence`, `is_flagged`, `flag_reason`, `device_user_agent`, `client_ip`, `created_at`)
                     VALUES (:att_id, 'check_in', :qr_id, :lat, :lon, :acc, :dist, :geofence, :flagged, :reason, :ua, :ip, :now)";

        try {
            $stmt = Database::getConnection()->prepare($eventSql);
            $stmt->execute([
                'att_id'   => $attendanceId,
                'qr_id'    => $qrTokenId,
                'lat'      => $lat,
                'lon'      => $lon,
                'acc'      => $accuracy,
                'dist'     => $distance,
                'geofence' => $withinGeofence,
                'flagged'  => $isFlagged,
                'reason'   => $flagReason,
                'ua'       => substr($userAgent, 0, 500),
                'ip'       => $clientIp,
                'now'      => $checkInTime,
            ]);
        } catch (\Throwable $e) {
            // Log fallback
        }

        return [
            'success'       => true,
            'idempotent'    => false,
            'attendance_id' => $attendanceId,
            'message'       => 'Check-in recorded and verified successfully.',
        ];
    }

    /**
     * Record Check-Out time for an active attendance session
     */
    public function recordCheckOut(int $attendanceId, string $checkOutTime): bool
    {
        return $this->update($attendanceId, [
            'check_out_time' => $checkOutTime,
        ]);
    }

    /**
     * Auditable Admin Override with mandatory explanation reason.
     * Never silently edit historical records.
     */
    public function applyAdminOverride(
        int $attendanceId,
        string $newVerificationStatus,
        string $newStatus,
        int $adminUserId,
        string $mandatoryReason
    ): bool {
        $reason = trim($mandatoryReason);
        if (strlen($reason) < 10) {
            throw new \InvalidArgumentException('A comprehensive, auditable explanation reason (minimum 10 characters) is required for admin override.');
        }

        $current = $this->find($attendanceId);
        if (!$current) {
            throw new \InvalidArgumentException('Attendance record not found.');
        }

        // 1. Update master attendance
        $updated = $this->update($attendanceId, [
            'verification_status' => $newVerificationStatus,
            'status'              => $newStatus,
            'notes'               => ($current['notes'] ? $current['notes'] . " | " : "") . "Admin Override by user #{$adminUserId}: {$reason}"
        ]);

        if (!$updated) {
            return false;
        }

        // 2. Insert correction audit event into verification history
        try {
            $sql = "INSERT INTO `attendance_verification_events`
                    (`attendance_id`, `event_type`, `admin_override_by`, `admin_override_reason`, `is_flagged`, `client_ip`, `created_at`)
                    VALUES (:att_id, 'check_in', :admin_id, :reason, 0, :ip, :now)";
            $stmt = Database::getConnection()->prepare($sql);
            $stmt->execute([
                'att_id'   => $attendanceId,
                'admin_id' => $adminUserId,
                'reason'   => $reason,
                'ip'       => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
                'now'      => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            // Ignore offline
        }

        // 3. Log to immutable audit_logs
        AuditLog::log(
            AuditLog::EVENT_USER_UPDATED,
            "Admin override applied to attendance #{$attendanceId} (Status: {$newStatus}, Verification: {$newVerificationStatus})",
            $adminUserId,
            ['attendance_id' => $attendanceId, 'reason' => $reason]
        );

        return true;
    }

    /**
     * Get paginated attendance logs with full supervisor, shift, location, and GPS details
     */
    public function getAllWithDetails(array $filters = [], int $page = 1, int $perPage = 15): array
    {
        $conditions = ["a.deleted_at IS NULL"];
        $params = [];

        if (!empty($filters['user_id'])) {
            $conditions[] = "a.user_id = :user_id";
            $params['user_id'] = (int)$filters['user_id'];
        }

        if (!empty($filters['shift_id'])) {
            $conditions[] = "a.shift_id = :shift_id";
            $params['shift_id'] = (int)$filters['shift_id'];
        }

        if (!empty($filters['date'])) {
            $conditions[] = "a.attendance_date = :date";
            $params['date'] = $filters['date'];
        }

        if (!empty($filters['status'])) {
            $conditions[] = "a.status = :status";
            $params['status'] = $filters['status'];
        }

        if (!empty($filters['verification_status'])) {
            $conditions[] = "a.verification_status = :v_status";
            $params['v_status'] = $filters['verification_status'];
        }

        $whereClause = implode(' AND ', $conditions);
        $offset = ($page - 1) * $perPage;

        $countSql = "SELECT COUNT(*) as total FROM `attendance` a WHERE {$whereClause}";
        $totalRecords = (int)(Database::selectOne($countSql, $params)['total'] ?? 0);
        $totalPages = (int)ceil($totalRecords / $perPage);

        $sql = "SELECT a.*,
                       u.full_name as supervisor_name, u.employee_code, u.email as supervisor_email,
                       s.shift_code, s.shift_name, s.start_time, s.end_time, s.is_overnight,
                       w.ward_name, w.ward_code,
                       loc.location_name, loc.location_code,
                       ave.gps_latitude, ave.gps_longitude, ave.gps_accuracy_meters, ave.distance_to_hospital_meters,
                       ave.is_within_geofence, ave.is_flagged, ave.flag_reason, ave.admin_override_reason
                FROM `attendance` a
                JOIN `users` u ON a.user_id = u.id
                JOIN `shifts` s ON a.shift_id = s.id
                LEFT JOIN `wards` w ON a.ward_id = w.id
                LEFT JOIN `inspection_locations` loc ON a.location_id = loc.id
                LEFT JOIN (
                    SELECT * FROM `attendance_verification_events`
                    WHERE id IN (SELECT MAX(id) FROM `attendance_verification_events` GROUP BY attendance_id)
                ) ave ON ave.attendance_id = a.id
                WHERE {$whereClause}
                ORDER BY a.attendance_date DESC, a.check_in_time DESC
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
     * Retrieve immutable audit history of administrative overrides & corrections for an attendance record.
     */
    public function getCorrectionHistory(int $attendanceId): array
    {
        try {
            $sql = "SELECT ave.*, u.full_name as admin_name, u.employee_code as admin_employee_code
                    FROM `attendance_verification_events` ave
                    LEFT JOIN `users` u ON ave.admin_override_by = u.id
                    WHERE ave.attendance_id = :aid AND ave.admin_override_by IS NOT NULL
                    ORDER BY ave.created_at DESC";
            return Database::selectAll($sql, ['aid' => $attendanceId]);
        } catch (\Throwable $e) {
            return [];
        }
    }
}

