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
use DateTime;
use PDO;

/**
 * Report & Analytics Service for Sheth L.G. General Hospital
 * Handles multi-criteria parameterized reporting with strict supervisor isolation,
 * date preset validation, and comprehensive metrics generation.
 */
class ReportService
{
    /**
     * Resolve and validate date range from filters (preset or custom)
     */
    public function resolveDateRange(array $filters): array
    {
        $preset = $filters['date_preset'] ?? 'this_month';
        $now = new DateTime('now', new \DateTimeZone('Asia/Kolkata'));

        switch ($preset) {
            case 'today':
                $start = $now->format('Y-m-d');
                $end = $start;
                break;

            case 'yesterday':
                $yesterday = (clone $now)->modify('-1 day');
                $start = $yesterday->format('Y-m-d');
                $end = $start;
                break;

            case 'this_week':
                $mon = (clone $now)->modify('monday this week');
                $sun = (clone $now)->modify('sunday this week');
                $start = $mon->format('Y-m-d');
                $end = $sun->format('Y-m-d');
                break;

            case 'last_month':
                $firstDayLastMonth = (clone $now)->modify('first day of last month');
                $lastDayLastMonth = (clone $now)->modify('last day of last month');
                $start = $firstDayLastMonth->format('Y-m-d');
                $end = $lastDayLastMonth->format('Y-m-d');
                break;

            case 'custom':
                $start = !empty($filters['start_date']) && $this->isValidDate($filters['start_date'])
                    ? $filters['start_date']
                    : $now->modify('first day of this month')->format('Y-m-d');
                $end = !empty($filters['end_date']) && $this->isValidDate($filters['end_date'])
                    ? $filters['end_date']
                    : (new DateTime('now', new \DateTimeZone('Asia/Kolkata')))->format('Y-m-d');
                break;

            case 'this_month':
            default:
                $preset = 'this_month';
                $start = (clone $now)->modify('first day of this month')->format('Y-m-d');
                $end = (clone $now)->modify('last day of this month')->format('Y-m-d');
                break;
        }

        // Ensure start is not after end
        if ($start > $end) {
            $tmp = $start;
            $start = $end;
            $end = $tmp;
        }

        return [
            'preset'     => $preset,
            'start_date' => $start,
            'end_date'   => $end,
        ];
    }

    private function isValidDate(string $date): bool
    {
        $d = DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }

    /**
     * Get wards assigned to supervisor to enforce strict data isolation
     */
    public function getSupervisorAssignedWardIds(int $userId): array
    {
        $sql = "SELECT DISTINCT ward_id FROM `supervisor_assignments` 
                WHERE user_id = :uid AND status = 'active' AND ward_id IS NOT NULL";
        $wardRows = Database::selectAll($sql, ['uid' => $userId]);
        $wardIds = array_column($wardRows, 'ward_id');

        // Also check building / floor assignments
        $sqlBldg = "SELECT DISTINCT w.id as ward_id 
                    FROM `supervisor_assignments` sa
                    JOIN `wards` w ON (sa.floor_id IS NOT NULL AND w.floor_id = sa.floor_id) 
                                   OR (sa.building_id IS NOT NULL AND sa.floor_id IS NULL AND w.building_id = sa.building_id)
                    WHERE sa.user_id = :uid AND sa.status = 'active'";
        $bldgRows = Database::selectAll($sqlBldg, ['uid' => $userId]);
        $allWards = array_unique(array_merge($wardIds, array_column($bldgRows, 'ward_id')));

        return array_map('intval', array_filter($allWards));
    }

    // =========================================================================
    // 1. INSPECTIONS SUMMARY REPORT
    // =========================================================================
    public function getInspectionSummaryReport(array $filters, array $currentUser): array
    {
        $dates = $this->resolveDateRange($filters);
        $conditions = ["DATE(i.started_at) BETWEEN :start_date AND :end_date", "i.deleted_at IS NULL"];
        $params = [
            'start_date' => $dates['start_date'],
            'end_date'   => $dates['end_date'],
        ];

        if (!empty($filters['building_id'])) {
            $conditions[] = "i.building_id = :bldg_id";
            $params['bldg_id'] = (int)$filters['building_id'];
        }
        if (!empty($filters['floor_id'])) {
            $conditions[] = "i.floor_id = :floor_id";
            $params['floor_id'] = (int)$filters['floor_id'];
        }
        if (!empty($filters['ward_id'])) {
            $conditions[] = "i.ward_id = :ward_id";
            $params['ward_id'] = (int)$filters['ward_id'];
        }
        if (!empty($filters['shift_id'])) {
            $conditions[] = "i.shift_id = :shift_id";
            $params['shift_id'] = (int)$filters['shift_id'];
        }
        if (!empty($filters['status'])) {
            $conditions[] = "i.status = :status";
            $params['status'] = $filters['status'];
        }

        // Supervisor Scoping
        if (($currentUser['role'] ?? '') === 'supervisor') {
            $scopedUserId = (int)$currentUser['id'];
            $assignedWards = $this->getSupervisorAssignedWardIds($scopedUserId);
            if (!empty($assignedWards)) {
                $inWards = implode(',', $assignedWards);
                $conditions[] = "(i.inspector_user_id = {$scopedUserId} OR i.ward_id IN ({$inWards}))";
            } else {
                $conditions[] = "i.inspector_user_id = :scoped_user_id";
                $params['scoped_user_id'] = $scopedUserId;
            }
        } elseif (!empty($filters['supervisor_id'])) {
            $conditions[] = "i.inspector_user_id = :sup_id";
            $params['sup_id'] = (int)$filters['supervisor_id'];
        }

        $whereClause = implode(" AND ", $conditions);

        // Fetch detailed rows
        $sql = "SELECT i.*, 
                       b.name as building_name, b.code as building_code,
                       f.floor_number, f.name as floor_name,
                       w.name as ward_name, w.code as ward_code,
                       u.name as inspector_name, u.username as inspector_username,
                       sh.name as shift_name
                FROM `inspections` i
                JOIN `buildings` b ON i.building_id = b.id
                JOIN `floors` f ON i.floor_id = f.id
                JOIN `wards` w ON i.ward_id = w.id
                JOIN `users` u ON i.inspector_user_id = u.id
                LEFT JOIN `shifts` sh ON i.shift_id = sh.id
                WHERE {$whereClause}
                ORDER BY i.started_at DESC";

        $rows = Database::selectAll($sql, $params);

        // Calculate Summary Metrics
        $total = count($rows);
        $completed = 0;
        $totalScore = 0.0;
        $scoredCount = 0;
        $totalPassed = 0;
        $totalFailed = 0;
        $totalCritical = 0;
        $qrVerifiedCount = 0;

        foreach ($rows as $r) {
            if ($r['status'] === 'approved' || $r['status'] === 'submitted') {
                $completed++;
            }
            if ($r['score_percentage'] !== null) {
                $totalScore += (float)$r['score_percentage'];
                $scoredCount++;
            }
            $totalPassed += (int)$r['passed_items'];
            $totalFailed += (int)$r['failed_items'];
            $totalCritical += (int)$r['critical_issues_count'];
            if (!empty($r['qr_verified'])) {
                $qrVerifiedCount++;
            }
        }

        $avgScore = $scoredCount > 0 ? round($totalScore / $scoredCount, 1) : 0.0;
        $qrRate = $total > 0 ? round(($qrVerifiedCount / $total) * 100, 1) : 0.0;

        return [
            'type'        => 'inspections_summary',
            'title'       => 'Daily, Weekly & Monthly Inspections Summary',
            'dates'       => $dates,
            'kpis'        => [
                'total_inspections' => $total,
                'completed'         => $completed,
                'avg_score'         => $avgScore,
                'total_passed'      => $totalPassed,
                'total_failed'      => $totalFailed,
                'total_critical'    => $totalCritical,
                'qr_verified_rate'  => $qrRate,
            ],
            'rows'        => $rows,
        ];
    }

    // =========================================================================
    // 2. FLOOR & WARD COMPLETION REPORT
    // =========================================================================
    public function getCompletionReport(array $filters, array $currentUser): array
    {
        $dates = $this->resolveDateRange($filters);
        $wardConditions = ["w.status = 'active'"];
        $wardParams = [];

        if (!empty($filters['building_id'])) {
            $wardConditions[] = "w.building_id = :bldg_id";
            $wardParams['bldg_id'] = (int)$filters['building_id'];
        }
        if (!empty($filters['floor_id'])) {
            $wardConditions[] = "w.floor_id = :floor_id";
            $wardParams['floor_id'] = (int)$filters['floor_id'];
        }
        if (!empty($filters['ward_id'])) {
            $wardConditions[] = "w.id = :ward_id";
            $wardParams['ward_id'] = (int)$filters['ward_id'];
        }

        // Supervisor Scoping: restrict wards to supervisor's scope
        if (($currentUser['role'] ?? '') === 'supervisor') {
            $scopedWards = $this->getSupervisorAssignedWardIds((int)$currentUser['id']);
            if (!empty($scopedWards)) {
                $inList = implode(',', $scopedWards);
                $wardConditions[] = "w.id IN ({$inList})";
            } else {
                $wardConditions[] = "1=0"; // No wards assigned
            }
        }

        $whereWards = implode(" AND ", $wardConditions);

        $sql = "SELECT w.id as ward_id, w.name as ward_name, w.code as ward_code,
                       f.id as floor_id, f.name as floor_name, f.floor_number,
                       b.id as building_id, b.name as building_name, b.code as building_code,
                       COUNT(s.id) as total_scheduled,
                       SUM(CASE WHEN s.status = 'completed' THEN 1 ELSE 0 END) as completed_count,
                       SUM(CASE WHEN s.status = 'pending' THEN 1 ELSE 0 END) as pending_count,
                       SUM(CASE WHEN s.status = 'missed' THEN 1 ELSE 0 END) as missed_count,
                       ROUND(AVG(i.score_percentage), 1) as avg_score,
                       COALESCE(SUM(i.critical_issues_count), 0) as critical_issues
                FROM `wards` w
                JOIN `floors` f ON w.floor_id = f.id
                JOIN `buildings` b ON w.building_id = b.id
                LEFT JOIN `inspection_schedules` s ON w.id = s.ward_id 
                     AND s.scheduled_date BETWEEN :start_date AND :end_date
                LEFT JOIN `inspections` i ON s.id = i.schedule_id AND i.deleted_at IS NULL
                WHERE {$whereWards}
                GROUP BY w.id, w.name, w.code, f.id, f.name, f.floor_number, b.id, b.name, b.code
                ORDER BY b.id ASC, f.floor_number ASC, w.name ASC";

        $params = array_merge($wardParams, [
            'start_date' => $dates['start_date'],
            'end_date'   => $dates['end_date'],
        ]);

        $rows = Database::selectAll($sql, $params);

        // Augment with completion rate %
        $totalScheduled = 0;
        $totalCompleted = 0;
        $totalPending = 0;
        $totalMissed = 0;

        foreach ($rows as &$r) {
            $sched = (int)$r['total_scheduled'];
            $comp = (int)$r['completed_count'];
            $totalScheduled += $sched;
            $totalCompleted += $comp;
            $totalPending += (int)$r['pending_count'];
            $totalMissed += (int)$r['missed_count'];

            $r['completion_rate'] = $sched > 0 ? round(($comp / $sched) * 100, 1) : 0.0;
        }
        unset($r);

        $overallRate = $totalScheduled > 0 ? round(($totalCompleted / $totalScheduled) * 100, 1) : 0.0;

        return [
            'type'        => 'completion_report',
            'title'       => 'Floor-wise & Ward-wise Completion Report',
            'dates'       => $dates,
            'kpis'        => [
                'total_scheduled' => $totalScheduled,
                'total_completed' => $totalCompleted,
                'total_pending'   => $totalPending,
                'total_missed'    => $totalMissed,
                'completion_rate' => $overallRate,
            ],
            'rows'        => $rows,
        ];
    }

    // =========================================================================
    // 3. SUPERVISOR ATTENDANCE & VERIFICATION REPORT
    // =========================================================================
    public function getAttendanceReport(array $filters, array $currentUser): array
    {
        $dates = $this->resolveDateRange($filters);
        $conditions = ["a.attendance_date BETWEEN :start_date AND :end_date", "a.deleted_at IS NULL"];
        $params = [
            'start_date' => $dates['start_date'],
            'end_date'   => $dates['end_date'],
        ];

        if (!empty($filters['shift_id'])) {
            $conditions[] = "a.shift_id = :shift_id";
            $params['shift_id'] = (int)$filters['shift_id'];
        }
        if (!empty($filters['status'])) {
            $conditions[] = "a.status = :status";
            $params['status'] = $filters['status'];
        }
        if (!empty($filters['verification_status'])) {
            $conditions[] = "a.verification_status = :ver_status";
            $params['ver_status'] = $filters['verification_status'];
        }

        // Supervisor Scoping
        if (($currentUser['role'] ?? '') === 'supervisor') {
            $conditions[] = "a.user_id = :scoped_uid";
            $params['scoped_uid'] = (int)$currentUser['id'];
        } elseif (!empty($filters['supervisor_id'])) {
            $conditions[] = "a.user_id = :sup_id";
            $params['sup_id'] = (int)$filters['supervisor_id'];
        }

        $whereClause = implode(" AND ", $conditions);

        $sql = "SELECT a.*, 
                       u.name as supervisor_name, u.username as employee_id, u.email as supervisor_email,
                       sh.name as shift_name, sh.start_time as shift_start, sh.end_time as shift_end,
                       ve.distance_to_hospital_meters, ve.is_within_geofence, ve.is_flagged, ve.flag_reason
                FROM `attendance` a
                JOIN `users` u ON a.user_id = u.id
                JOIN `shifts` sh ON a.shift_id = sh.id
                LEFT JOIN `attendance_verification_events` ve ON a.id = ve.attendance_id AND ve.event_type = 'check_in'
                WHERE {$whereClause}
                ORDER BY a.attendance_date DESC, a.check_in_time DESC";

        $rows = Database::selectAll($sql, $params);

        $total = count($rows);
        $verifiedCount = 0;
        $flaggedCount = 0;
        $lateCount = 0;
        $presentCount = 0;

        foreach ($rows as $r) {
            if ($r['verification_status'] === 'verified') {
                $verifiedCount++;
            }
            if ($r['verification_status'] === 'suspicious_gps' || !empty($r['is_flagged'])) {
                $flaggedCount++;
            }
            if ($r['status'] === 'late') {
                $lateCount++;
            }
            if ($r['status'] === 'present') {
                $presentCount++;
            }
        }

        $verifiedRate = $total > 0 ? round(($verifiedCount / $total) * 100, 1) : 0.0;

        return [
            'type'        => 'attendance_report',
            'title'       => 'Supervisor Attendance & GPS Verification Audit',
            'dates'       => $dates,
            'kpis'        => [
                'total_attendance' => $total,
                'verified_count'   => $verifiedCount,
                'verified_rate'    => $verifiedRate,
                'flagged_count'    => $flaggedCount,
                'late_count'       => $lateCount,
                'present_count'    => $presentCount,
            ],
            'rows'        => $rows,
        ];
    }

    // =========================================================================
    // 4. EXCEPTIONS REPORT (Pending, Overdue, Missed)
    // =========================================================================
    public function getExceptionsReport(array $filters, array $currentUser): array
    {
        $dates = $this->resolveDateRange($filters);
        $today = date('Y-m-d');

        // Look for schedules that are missed, pending, in_progress, or overdue
        $conditions = [
            "s.scheduled_date BETWEEN :start_date AND :end_date",
            "(s.status IN ('pending', 'in_progress', 'missed') OR (s.status != 'completed' AND s.scheduled_date < :today))"
        ];
        $params = [
            'start_date' => $dates['start_date'],
            'end_date'   => $dates['end_date'],
            'today'      => $today,
        ];

        if (!empty($filters['building_id'])) {
            $conditions[] = "w.building_id = :bldg_id";
            $params['bldg_id'] = (int)$filters['building_id'];
        }
        if (!empty($filters['floor_id'])) {
            $conditions[] = "w.floor_id = :floor_id";
            $params['floor_id'] = (int)$filters['floor_id'];
        }
        if (!empty($filters['ward_id'])) {
            $conditions[] = "s.ward_id = :ward_id";
            $params['ward_id'] = (int)$filters['ward_id'];
        }
        if (!empty($filters['shift_id'])) {
            $conditions[] = "s.shift_id = :shift_id";
            $params['shift_id'] = (int)$filters['shift_id'];
        }

        // Supervisor Scoping
        if (($currentUser['role'] ?? '') === 'supervisor') {
            $scopedUid = (int)$currentUser['id'];
            $scopedWards = $this->getSupervisorAssignedWardIds($scopedUid);
            if (!empty($scopedWards)) {
                $inWards = implode(',', $scopedWards);
                $conditions[] = "(s.assigned_user_id = {$scopedUid} OR s.ward_id IN ({$inWards}))";
            } else {
                $conditions[] = "s.assigned_user_id = :scoped_uid";
                $params['scoped_uid'] = $scopedUid;
            }
        } elseif (!empty($filters['supervisor_id'])) {
            $conditions[] = "s.assigned_user_id = :sup_id";
            $params['sup_id'] = (int)$filters['supervisor_id'];
        }

        $whereClause = implode(" AND ", $conditions);

        $sql = "SELECT s.*, 
                       w.name as ward_name, w.code as ward_code,
                       f.name as floor_name, f.floor_number,
                       b.name as building_name, b.code as building_code,
                       u.name as assigned_supervisor, u.username as supervisor_username,
                       sh.name as shift_name, sh.start_time as shift_start, sh.end_time as shift_end,
                       ctv.version_number
                FROM `inspection_schedules` s
                JOIN `wards` w ON s.ward_id = w.id
                JOIN `floors` f ON w.floor_id = f.id
                JOIN `buildings` b ON w.building_id = b.id
                LEFT JOIN `users` u ON s.assigned_user_id = u.id
                LEFT JOIN `shifts` sh ON s.shift_id = sh.id
                LEFT JOIN `checklist_template_versions` ctv ON s.template_version_id = ctv.id
                WHERE {$whereClause}
                ORDER BY s.scheduled_date DESC, s.status ASC";

        $rows = Database::selectAll($sql, $params);

        $missedCount = 0;
        $overdueCount = 0;
        $pendingCount = 0;

        foreach ($rows as &$r) {
            $schedDate = $r['scheduled_date'];
            $isOverdue = ($r['status'] === 'pending' || $r['status'] === 'in_progress') && $schedDate < $today;
            $r['is_overdue'] = $isOverdue;

            if ($r['status'] === 'missed') {
                $r['exception_type'] = 'Missed';
                $missedCount++;
            } elseif ($isOverdue) {
                $r['exception_type'] = 'Overdue';
                $overdueCount++;
            } else {
                $r['exception_type'] = 'Pending Action';
                $pendingCount++;
            }
        }
        unset($r);

        return [
            'type'        => 'exceptions_report',
            'title'       => 'Pending, Overdue & Missed Inspections Exception Report',
            'dates'       => $dates,
            'kpis'        => [
                'total_exceptions' => count($rows),
                'missed_count'     => $missedCount,
                'overdue_count'    => $overdueCount,
                'pending_count'    => $pendingCount,
            ],
            'rows'        => $rows,
        ];
    }

    // =========================================================================
    // 5. CHECKLIST FAILURES & CRITICAL FINDINGS REPORT
    // =========================================================================
    public function getFailuresReport(array $filters, array $currentUser): array
    {
        $dates = $this->resolveDateRange($filters);
        $conditions = [
            "DATE(i.started_at) BETWEEN :start_date AND :end_date",
            "i.deleted_at IS NULL",
            "(ir.is_compliant = 0 OR ir.is_critical_failure = 1)"
        ];
        $params = [
            'start_date' => $dates['start_date'],
            'end_date'   => $dates['end_date'],
        ];

        if (!empty($filters['building_id'])) {
            $conditions[] = "i.building_id = :bldg_id";
            $params['bldg_id'] = (int)$filters['building_id'];
        }
        if (!empty($filters['floor_id'])) {
            $conditions[] = "i.floor_id = :floor_id";
            $params['floor_id'] = (int)$filters['floor_id'];
        }
        if (!empty($filters['ward_id'])) {
            $conditions[] = "i.ward_id = :ward_id";
            $params['ward_id'] = (int)$filters['ward_id'];
        }
        if (!empty($filters['critical_only'])) {
            $conditions[] = "ir.is_critical_failure = 1";
        }

        // Supervisor Scoping
        if (($currentUser['role'] ?? '') === 'supervisor') {
            $scopedUid = (int)$currentUser['id'];
            $scopedWards = $this->getSupervisorAssignedWardIds($scopedUid);
            if (!empty($scopedWards)) {
                $inWards = implode(',', $scopedWards);
                $conditions[] = "(i.inspector_user_id = {$scopedUid} OR i.ward_id IN ({$inWards}))";
            } else {
                $conditions[] = "i.inspector_user_id = :scoped_uid";
                $params['scoped_uid'] = $scopedUid;
            }
        } elseif (!empty($filters['supervisor_id'])) {
            $conditions[] = "i.inspector_user_id = :sup_id";
            $params['sup_id'] = (int)$filters['supervisor_id'];
        }

        $whereClause = implode(" AND ", $conditions);

        $sql = "SELECT ir.*, 
                       i.inspection_number, i.started_at as inspection_date,
                       b.name as building_name, b.code as building_code,
                       f.name as floor_name, f.floor_number,
                       w.name as ward_name, w.code as ward_code,
                       u.name as inspector_name, u.username as inspector_username,
                       ci.item_label, ci.severity
                FROM `inspection_responses` ir
                JOIN `inspections` i ON ir.inspection_id = i.id
                JOIN `checklist_items` ci ON ir.checklist_item_id = ci.id
                JOIN `buildings` b ON i.building_id = b.id
                JOIN `floors` f ON i.floor_id = f.id
                JOIN `wards` w ON i.ward_id = w.id
                JOIN `users` u ON i.inspector_user_id = u.id
                WHERE {$whereClause}
                ORDER BY ir.is_critical_failure DESC, i.started_at DESC";

        $rows = Database::selectAll($sql, $params);

        $totalFailures = count($rows);
        $criticalCount = 0;
        $uniqueInspections = [];

        foreach ($rows as $r) {
            if (!empty($r['is_critical_failure'])) {
                $criticalCount++;
            }
            $uniqueInspections[$r['inspection_id']] = true;
        }

        return [
            'type'        => 'failures_report',
            'title'       => 'Checklist Failures & Critical Findings Report',
            'dates'       => $dates,
            'kpis'        => [
                'total_failures'     => $totalFailures,
                'critical_findings'  => $criticalCount,
                'affected_audits'    => count($uniqueInspections),
            ],
            'rows'        => $rows,
        ];
    }

    // =========================================================================
    // 6. COMPLAINT STATUS AND SLA BREACH REPORT
    // =========================================================================
    public function getSlaReport(array $filters, array $currentUser): array
    {
        $dates = $this->resolveDateRange($filters);
        $conditions = [
            "DATE(c.created_at) BETWEEN :start_date AND :end_date",
            "c.deleted_at IS NULL"
        ];
        $params = [
            'start_date' => $dates['start_date'],
            'end_date'   => $dates['end_date'],
        ];

        if (!empty($filters['building_id'])) {
            $conditions[] = "c.building_id = :bldg_id";
            $params['bldg_id'] = (int)$filters['building_id'];
        }
        if (!empty($filters['floor_id'])) {
            $conditions[] = "c.floor_id = :floor_id";
            $params['floor_id'] = (int)$filters['floor_id'];
        }
        if (!empty($filters['ward_id'])) {
            $conditions[] = "c.ward_id = :ward_id";
            $params['ward_id'] = (int)$filters['ward_id'];
        }
        if (!empty($filters['status'])) {
            $conditions[] = "c.status = :status";
            $params['status'] = $filters['status'];
        }
        if (!empty($filters['priority'])) {
            $conditions[] = "c.priority = :priority";
            $params['priority'] = $filters['priority'];
        }

        // Supervisor Scoping
        if (($currentUser['role'] ?? '') === 'supervisor') {
            $scopedUid = (int)$currentUser['id'];
            $scopedWards = $this->getSupervisorAssignedWardIds($scopedUid);
            if (!empty($scopedWards)) {
                $inWards = implode(',', $scopedWards);
                $conditions[] = "(c.reported_by = {$scopedUid} OR c.ward_id IN ({$inWards}))";
            } else {
                $conditions[] = "c.reported_by = :scoped_uid";
                $params['scoped_uid'] = $scopedUid;
            }
        }

        $whereClause = implode(" AND ", $conditions);

        $sql = "SELECT c.*, 
                       b.name as building_name, b.code as building_code,
                       f.name as floor_name, f.floor_number,
                       w.name as ward_name, w.code as ward_code,
                       u.name as reporter_name,
                       sp.policy_name, sp.response_time_hours, sp.resolution_time_hours
                FROM `complaints` c
                JOIN `buildings` b ON c.building_id = b.id
                JOIN `floors` f ON c.floor_id = f.id
                JOIN `wards` w ON c.ward_id = w.id
                JOIN `users` u ON c.reported_by = u.id
                LEFT JOIN `sla_policies` sp ON c.sla_policy_id = sp.id
                WHERE {$whereClause}
                ORDER BY c.created_at DESC";

        $rows = Database::selectAll($sql, $params);

        $now = new DateTime('now', new \DateTimeZone('Asia/Kolkata'));
        $total = count($rows);
        $resolvedCount = 0;
        $openCount = 0;
        $slaBreaches = 0;
        $criticalCount = 0;

        foreach ($rows as &$r) {
            $isResolved = in_array($r['status'], ['resolved', 'closed'], true);
            if ($isResolved) {
                $resolvedCount++;
            } else {
                $openCount++;
            }

            if ($r['priority'] === 'critical') {
                $criticalCount++;
            }

            // SLA breach check
            $isBreached = false;
            if (!empty($r['due_date'])) {
                $dueDate = new DateTime($r['due_date'], new \DateTimeZone('Asia/Kolkata'));
                if (!$isResolved && $now > $dueDate) {
                    $isBreached = true;
                } elseif ($isResolved && !empty($r['resolved_at'])) {
                    $resDate = new DateTime($r['resolved_at'], new \DateTimeZone('Asia/Kolkata'));
                    if ($resDate > $dueDate) {
                        $isBreached = true;
                    }
                }
            }

            $r['is_sla_breached'] = $isBreached;
            if ($isBreached) {
                $slaBreaches++;
            }
        }
        unset($r);

        $complianceRate = $total > 0 ? round((($total - $slaBreaches) / $total) * 100, 1) : 100.0;

        return [
            'type'        => 'sla_report',
            'title'       => 'Complaint Status & SLA Breach Analysis Report',
            'dates'       => $dates,
            'kpis'        => [
                'total_complaints' => $total,
                'resolved_count'   => $resolvedCount,
                'open_count'       => $openCount,
                'critical_count'   => $criticalCount,
                'sla_breaches'     => $slaBreaches,
                'compliance_rate'  => $complianceRate,
            ],
            'rows'        => $rows,
        ];
    }

    /**
     * Get lookup dropdown choices (buildings, floors, wards, supervisors, shifts)
     */
    public function getFilterDropdownOptions(array $currentUser): array
    {
        $buildings = Database::selectAll("SELECT id, name, code FROM `buildings` WHERE status = 'active' ORDER BY name ASC");
        $floors = Database::selectAll("SELECT id, building_id, name, floor_number FROM `floors` WHERE status = 'active' ORDER BY building_id ASC, floor_number ASC");
        
        $wardSql = "SELECT id, building_id, floor_id, name, code FROM `wards` WHERE status = 'active'";
        $wardParams = [];
        if (($currentUser['role'] ?? '') === 'supervisor') {
            $assignedWards = $this->getSupervisorAssignedWardIds((int)$currentUser['id']);
            if (!empty($assignedWards)) {
                $inWards = implode(',', $assignedWards);
                $wardSql .= " AND id IN ({$inWards})";
            }
        }
        $wardSql .= " ORDER BY name ASC";
        $wards = Database::selectAll($wardSql, $wardParams);

        $supervisors = Database::selectAll("SELECT id, name, username FROM `users` WHERE role = 'supervisor' AND status = 'active' ORDER BY name ASC");
        $shifts = Database::selectAll("SELECT id, name, start_time, end_time FROM `shifts` WHERE status = 'active' ORDER BY start_time ASC");

        return [
            'buildings'   => $buildings,
            'floors'      => $floors,
            'wards'       => $wards,
            'supervisors' => $supervisors,
            'shifts'      => $shifts,
        ];
    }
}
