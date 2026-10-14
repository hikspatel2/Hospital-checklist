<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\Building;
use App\Models\Floor;
use App\Models\Shift;
use App\Models\SupervisorAssignment;
use App\Models\User;
use App\Models\Ward;
use PDO;

/**
 * Dashboard & Inspection Matrix Service
 * Provides database-backed KPI aggregations, facility matrix calculations,
 * supervisor permission scoping, and double-counting prevention.
 */
class DashboardService
{
    /**
     * Get Today's Inspection Metrics (Completed, Pending, Overdue, Missed, Total)
     */
    public function getInspectionMetrics(array $filters, array $currentUser): array
    {
        $date = !empty($filters['date']) ? $filters['date'] : date('Y-m-d');
        $shiftId = !empty($filters['shift_id']) ? (int)$filters['shift_id'] : null;
        $buildingId = !empty($filters['building_id']) ? (int)$filters['building_id'] : null;
        $floorId = !empty($filters['floor_id']) ? (int)$filters['floor_id'] : null;
        $wardId = !empty($filters['ward_id']) ? (int)$filters['ward_id'] : null;
        $supervisorId = !empty($filters['supervisor_id']) ? (int)$filters['supervisor_id'] : null;

        // Permissions scoping: if supervisor, restrict to their assigned work
        $scopedUserId = null;
        if (($currentUser['role'] ?? '') === 'supervisor') {
            $scopedUserId = (int)$currentUser['id'];
        } elseif ($supervisorId) {
            $scopedUserId = $supervisorId;
        }

        $conditions = ["s.scheduled_date = :date"];
        $params = [':date' => $date];

        if ($shiftId) {
            $conditions[] = "s.shift_id = :shift_id";
            $params[':shift_id'] = $shiftId;
        }
        if ($buildingId) {
            $conditions[] = "s.building_id = :bldg_id";
            $params[':bldg_id'] = $buildingId;
        }
        if ($floorId) {
            $conditions[] = "s.floor_id = :floor_id";
            $params[':floor_id'] = $floorId;
        }
        if ($wardId) {
            $conditions[] = "s.ward_id = :ward_id";
            $params[':ward_id'] = $wardId;
        }
        if ($scopedUserId) {
            $conditions[] = "s.assigned_user_id = :assigned_user";
            $params[':assigned_user'] = $scopedUserId;
        }

        $whereClause = implode(' AND ', $conditions);

        $metrics = [
            'total_scheduled' => 0,
            'completed'       => 0,
            'pending'         => 0,
            'overdue'         => 0,
            'missed'          => 0,
            'compliance_rate' => 0.0,
            'average_score'   => 0.0,
        ];

        try {
            $pdo = Database::getConnection();

            // Aggregated query directly on inspection_schedules without Cartesian joins
            $sql = "
                SELECT 
                    COUNT(*) AS total_scheduled,
                    COUNT(CASE WHEN s.status = 'completed' THEN 1 END) AS completed_count,
                    COUNT(CASE WHEN s.status IN ('scheduled', 'pending', 'in_progress') AND (s.scheduled_date > CURDATE() OR (s.scheduled_date = CURDATE() AND (s.window_end_time IS NULL OR s.window_end_time >= CURTIME()))) THEN 1 END) AS pending_count,
                    COUNT(CASE WHEN s.status = 'overdue' OR (s.status IN ('scheduled', 'pending', 'in_progress') AND (s.scheduled_date < CURDATE() OR (s.scheduled_date = CURDATE() AND s.window_end_time < CURTIME()))) THEN 1 END) AS overdue_count,
                    COUNT(CASE WHEN s.status = 'missed' THEN 1 END) AS missed_count
                FROM `inspection_schedules` s
                WHERE {$whereClause}
            ";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $res = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($res) {
                $metrics['total_scheduled'] = (int)$res['total_scheduled'];
                $metrics['completed']       = (int)$res['completed_count'];
                $metrics['pending']         = (int)$res['pending_count'];
                $metrics['overdue']         = (int)$res['overdue_count'];
                $metrics['missed']          = (int)$res['missed_count'];

                if ($metrics['total_scheduled'] > 0) {
                    $metrics['compliance_rate'] = round(($metrics['completed'] / $metrics['total_scheduled']) * 100, 1);
                }
            }

            // Average score of completed inspections for today
            $scoreSql = "
                SELECT AVG(i.score_percentage) AS avg_score
                FROM `inspections` i
                JOIN `inspection_schedules` s ON i.schedule_id = s.id
                WHERE {$whereClause} AND i.status = 'submitted'
            ";
            $scoreStmt = $pdo->prepare($scoreSql);
            $scoreStmt->execute($params);
            $avgScore = $scoreStmt->fetchColumn();
            if ($avgScore !== false && $avgScore !== null) {
                $metrics['average_score'] = round((float)$avgScore, 1);
            }
        } catch (\Throwable $e) {
            // DB fallback
        }

        return $metrics;
    }

    /**
     * Get Supervisor Attendance Metrics for Selected Date & Shift
     * "Do not count unverified attendance as verified."
     */
    public function getAttendanceMetrics(array $filters, array $currentUser): array
    {
        $date = !empty($filters['date']) ? $filters['date'] : date('Y-m-d');
        $shiftId = !empty($filters['shift_id']) ? (int)$filters['shift_id'] : null;

        $conditions = ["a.attendance_date = :date", "a.deleted_at IS NULL"];
        $params = [':date' => $date];

        if ($shiftId) {
            $conditions[] = "a.shift_id = :shift_id";
            $params[':shift_id'] = $shiftId;
        }

        // Supervisor scoping
        if (($currentUser['role'] ?? '') === 'supervisor') {
            $conditions[] = "a.user_id = :user_id";
            $params[':user_id'] = (int)$currentUser['id'];
        }

        $whereClause = implode(' AND ', $conditions);

        $metrics = [
            'verified_present'   => 0,
            'late'               => 0,
            'flagged_suspicious' => 0,
            'unverified_manual'  => 0,
            'absent'             => 0,
            'total_attendance'   => 0,
        ];

        try {
            $pdo = Database::getConnection();

            // Strict verification check:
            // Verified Present = status = 'present' AND verification_status IN ('verified', 'admin_override')
            $sql = "
                SELECT 
                    COUNT(CASE WHEN a.status = 'present' AND a.verification_status IN ('verified', 'admin_override') THEN 1 END) AS verified_present,
                    COUNT(CASE WHEN a.status = 'late' AND a.verification_status IN ('verified', 'admin_override') THEN 1 END) AS late_count,
                    COUNT(CASE WHEN a.status = 'flagged_review' OR a.verification_status = 'suspicious_gps' THEN 1 END) AS flagged_suspicious,
                    COUNT(CASE WHEN a.verification_status = 'manual_entry' THEN 1 END) AS unverified_manual,
                    COUNT(CASE WHEN a.status = 'absent' THEN 1 END) AS absent_count,
                    COUNT(*) AS total_attendance
                FROM `attendance` a
                WHERE {$whereClause}
            ";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $res = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($res) {
                $metrics['verified_present']   = (int)$res['verified_present'];
                $metrics['late']               = (int)$res['late_count'];
                $metrics['flagged_suspicious'] = (int)$res['flagged_suspicious'];
                $metrics['unverified_manual']  = (int)$res['unverified_manual'];
                $metrics['absent']             = (int)$res['absent_count'];
                $metrics['total_attendance']   = (int)$res['total_attendance'];
            }
        } catch (\Throwable $e) {
            // DB fallback
        }

        return $metrics;
    }

    /**
     * Get Open Complaints & SLA Breach Metrics
     */
    public function getComplaintMetrics(array $filters, array $currentUser): array
    {
        $date = !empty($filters['date']) ? $filters['date'] : date('Y-m-d');
        $buildingId = !empty($filters['building_id']) ? (int)$filters['building_id'] : null;
        $floorId = !empty($filters['floor_id']) ? (int)$filters['floor_id'] : null;
        $wardId = !empty($filters['ward_id']) ? (int)$filters['ward_id'] : null;

        $conditions = ["c.deleted_at IS NULL"];
        $params = [];

        if ($buildingId) {
            $conditions[] = "c.building_id = :bldg";
            $params[':bldg'] = $buildingId;
        }
        if ($floorId) {
            $conditions[] = "c.floor_id = :floor";
            $params[':floor'] = $floorId;
        }
        if ($wardId) {
            $conditions[] = "c.ward_id = :ward";
            $params[':ward'] = $wardId;
        }

        // Supervisor scoping: only see complaints assigned to them or reported by them
        if (($currentUser['role'] ?? '') === 'supervisor') {
            $conditions[] = "(c.assigned_to_user_id = :curr_user OR c.reported_by = :curr_user)";
            $params[':curr_user'] = (int)$currentUser['id'];
        }

        $whereClause = implode(' AND ', $conditions);

        $metrics = [
            'total_active'        => 0,
            'critical_high'       => 0,
            'response_breached'   => 0,
            'resolution_breached' => 0,
            'resolved_today'      => 0,
        ];

        try {
            $pdo = Database::getConnection();

            $sql = "
                SELECT 
                    COUNT(CASE WHEN c.status IN ('open', 'assigned', 'in_progress', 'on_hold') THEN 1 END) AS total_active,
                    COUNT(CASE WHEN c.status IN ('open', 'assigned', 'in_progress', 'on_hold') AND c.priority IN ('critical', 'high') THEN 1 END) AS critical_high,
                    COUNT(CASE WHEN c.status = 'open' AND (c.is_response_breached = 1 OR c.response_due_at < NOW()) THEN 1 END) AS response_breached,
                    COUNT(CASE WHEN c.status IN ('open', 'assigned', 'in_progress', 'on_hold') AND (c.is_resolution_breached = 1 OR c.resolution_due_at < NOW()) THEN 1 END) AS resolution_breached,
                    COUNT(CASE WHEN c.status = 'resolved' AND DATE(c.resolved_at) = :date THEN 1 END) AS resolved_today
                FROM `complaints` c
                WHERE {$whereClause}
            ";

            $stmt = $pdo->prepare($sql);
            $params[':date'] = $date;
            $stmt->execute($params);
            $res = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($res) {
                $metrics['total_active']        = (int)$res['total_active'];
                $metrics['critical_high']       = (int)$res['critical_high'];
                $metrics['response_breached']   = (int)$res['response_breached'];
                $metrics['resolution_breached'] = (int)$res['resolution_breached'];
                $metrics['resolved_today']      = (int)$res['resolved_today'];
            }
        } catch (\Throwable $e) {
            // DB fallback
        }

        return $metrics;
    }

    /**
     * Get Building/Floor/Ward-wise Inspection Matrix
     * Recreates the visual hierarchy:
     * - Grouped by Building -> Floor -> Ward
     * - Shows inspection status (Completed with score, Pending, Overdue, Missed, No Schedule)
     * - Shows assigned supervisor
     * - Shows active complaint count in ward
     */
    public function getInspectionMatrix(array $filters, array $currentUser): array
    {
        $date = !empty($filters['date']) ? $filters['date'] : date('Y-m-d');
        $shiftId = !empty($filters['shift_id']) ? (int)$filters['shift_id'] : null;
        $buildingId = !empty($filters['building_id']) ? (int)$filters['building_id'] : null;
        $floorId = !empty($filters['floor_id']) ? (int)$filters['floor_id'] : null;
        $wardId = !empty($filters['ward_id']) ? (int)$filters['ward_id'] : null;
        $supervisorId = !empty($filters['supervisor_id']) ? (int)$filters['supervisor_id'] : null;

        $matrix = [];

        try {
            $pdo = Database::getConnection();

            // 1. Fetch wards matching facility filters and supervisor scoping
            $wardConditions = ["w.deleted_at IS NULL", "w.status = 'active'"];
            $wardParams = [];

            if ($buildingId) {
                $wardConditions[] = "f.building_id = :bldg_id";
                $wardParams[':bldg_id'] = $buildingId;
            }
            if ($floorId) {
                $wardConditions[] = "w.floor_id = :floor_id";
                $wardParams[':floor_id'] = $floorId;
            }
            if ($wardId) {
                $wardConditions[] = "w.id = :ward_id";
                $wardParams[':ward_id'] = $wardId;
            }

            // Supervisor scoping: limit to wards assigned to the supervisor
            if (($currentUser['role'] ?? '') === 'supervisor') {
                $wardConditions[] = "EXISTS (
                    SELECT 1 FROM `supervisor_assignments` sa 
                    WHERE sa.user_id = :sup_user 
                      AND sa.status = 'active'
                      AND (sa.ward_id = w.id OR sa.floor_id = w.floor_id OR sa.building_id = f.building_id)
                )";
                $wardParams[':sup_user'] = (int)$currentUser['id'];
            }

            $wardWhere = implode(' AND ', $wardConditions);

            $sql = "
                SELECT 
                    w.id AS ward_id,
                    w.ward_code,
                    w.ward_name,
                    w.ward_type,
                    f.id AS floor_id,
                    f.floor_name,
                    f.floor_number,
                    b.id AS building_id,
                    b.code AS building_code,
                    b.name AS building_name
                FROM `wards` w
                JOIN `floors` f ON w.floor_id = f.id
                JOIN `buildings` b ON f.building_id = b.id
                WHERE {$wardWhere}
                ORDER BY b.id ASC, f.floor_number ASC, w.ward_name ASC
            ";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($wardParams);
            $wards = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($wards)) {
                return [];
            }

            // 2. Fetch today's inspection schedules for these wards
            $schedConditions = ["s.scheduled_date = :date"];
            $schedParams = [':date' => $date];

            if ($shiftId) {
                $schedConditions[] = "s.shift_id = :shift_id";
                $schedParams[':shift_id'] = $shiftId;
            }
            if ($supervisorId) {
                $schedConditions[] = "s.assigned_user_id = :sup_id";
                $schedParams[':sup_id'] = $supervisorId;
            }

            $schedWhere = implode(' AND ', $schedConditions);

            $schedSql = "
                SELECT 
                    s.id AS schedule_id,
                    s.ward_id,
                    s.shift_id,
                    s.status AS schedule_status,
                    s.window_start_time,
                    s.window_end_time,
                    s.assigned_user_id,
                    u.name AS supervisor_name,
                    sh.shift_name,
                    i.id AS inspection_id,
                    i.reference_number AS inspection_reference,
                    i.score_percentage,
                    i.status AS inspection_status,
                    i.critical_issues_count
                FROM `inspection_schedules` s
                LEFT JOIN `users` u ON s.assigned_user_id = u.id
                LEFT JOIN `shifts` sh ON s.shift_id = sh.id
                LEFT JOIN `inspections` i ON i.schedule_id = s.id AND i.status = 'submitted'
                WHERE {$schedWhere}
            ";

            $schedStmt = $pdo->prepare($schedSql);
            $schedStmt->execute($schedParams);
            $schedules = $schedStmt->fetchAll(PDO::FETCH_ASSOC);

            // Index schedules by ward_id
            $schedulesByWard = [];
            foreach ($schedules as $s) {
                $wId = (int)$s['ward_id'];
                if (!isset($schedulesByWard[$wId])) {
                    $schedulesByWard[$wId] = [];
                }
                $schedulesByWard[$wId][] = $s;
            }

            // 3. Fetch active complaint counts by ward
            $complaintSql = "
                SELECT ward_id, COUNT(*) AS active_count
                FROM `complaints`
                WHERE `deleted_at` IS NULL AND `status` IN ('open', 'assigned', 'in_progress', 'on_hold')
                GROUP BY ward_id
            ";
            $complaintCounts = $pdo->query($complaintSql)->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];

            // 4. Assemble the matrix
            $nowTime = date('H:i:s');
            $today = date('Y-m-d');

            foreach ($wards as $w) {
                $wId = (int)$w['ward_id'];
                $wardSchedules = $schedulesByWard[$wId] ?? [];
                $activeComplaints = (int)($complaintCounts[$wId] ?? 0);

                $statusState = 'NOT_SCHEDULED';
                $scheduleId = null;
                $inspectionId = null;
                $scorePercentage = null;
                $supervisorName = null;
                $shiftName = null;
                $criticalCount = 0;

                if (!empty($wardSchedules)) {
                    // Pick the primary schedule (completed takes precedence, then in_progress, etc.)
                    $primary = $wardSchedules[0];
                    foreach ($wardSchedules as $candidate) {
                        if ($candidate['schedule_status'] === 'completed') {
                            $primary = $candidate;
                            break;
                        }
                    }

                    $scheduleId = (int)$primary['schedule_id'];
                    $inspectionId = !empty($primary['inspection_id']) ? (int)$primary['inspection_id'] : null;
                    $scorePercentage = $primary['score_percentage'] !== null ? (float)$primary['score_percentage'] : null;
                    $supervisorName = $primary['supervisor_name'];
                    $shiftName = $primary['shift_name'];
                    $criticalCount = (int)($primary['critical_issues_count'] ?? 0);

                    if ($primary['schedule_status'] === 'completed') {
                        $statusState = 'COMPLETED';
                    } elseif ($primary['schedule_status'] === 'in_progress') {
                        $statusState = 'IN_PROGRESS';
                    } elseif ($primary['schedule_status'] === 'overdue' || ($date < $today) || ($date === $today && !empty($primary['window_end_time']) && $primary['window_end_time'] < $nowTime)) {
                        $statusState = 'OVERDUE';
                    } elseif ($primary['schedule_status'] === 'missed') {
                        $statusState = 'MISSED';
                    } else {
                        $statusState = 'PENDING';
                    }
                }

                $matrix[] = [
                    'ward_id'            => $wId,
                    'ward_code'          => $w['ward_code'],
                    'ward_name'          => $w['ward_name'],
                    'ward_type'          => $w['ward_type'],
                    'floor_id'           => (int)$w['floor_id'],
                    'floor_name'         => $w['floor_name'],
                    'floor_number'       => (int)$w['floor_number'],
                    'building_id'        => (int)$w['building_id'],
                    'building_code'      => $w['building_code'],
                    'building_name'      => $w['building_name'],
                    'status_state'       => $statusState,
                    'schedule_id'        => $scheduleId,
                    'inspection_id'      => $inspectionId,
                    'score_percentage'   => $scorePercentage,
                    'supervisor_name'    => $supervisorName,
                    'shift_name'         => $shiftName,
                    'critical_issues'    => $criticalCount,
                    'active_complaints'  => $activeComplaints,
                    'badge'              => self::formatMatrixBadge($statusState, $scorePercentage),
                ];
            }
        } catch (\Throwable $e) {
            // DB fallback
        }

        return $matrix;
    }

    /**
     * Format responsive visual badge for matrix cell
     */
    public static function formatMatrixBadge(string $status, ?float $score = null): array
    {
        return match ($status) {
            'COMPLETED' => [
                'label' => $score !== null ? number_format($score, 1) . '% Compliant' : 'Completed',
                'class' => 'bg-success text-white',
                'icon'  => 'bi-check-circle-fill',
            ],
            'IN_PROGRESS' => [
                'label' => 'In Progress',
                'class' => 'bg-primary text-white',
                'icon'  => 'bi-gear-wide-connected',
            ],
            'PENDING' => [
                'label' => 'Pending',
                'class' => 'bg-warning text-dark',
                'icon'  => 'bi-clock-history',
            ],
            'OVERDUE' => [
                'label' => 'Overdue',
                'class' => 'bg-danger text-white',
                'icon'  => 'bi-exclamation-triangle-fill',
            ],
            'MISSED' => [
                'label' => 'Missed Round',
                'class' => 'bg-dark text-white',
                'icon'  => 'bi-x-circle-fill',
            ],
            default => [
                'label' => 'Not Scheduled',
                'class' => 'bg-light text-muted border',
                'icon'  => 'bi-dash-circle',
            ],
        };
    }
}
