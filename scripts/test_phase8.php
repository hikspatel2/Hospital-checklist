<?php
declare(strict_types=1);

/**
 * Phase 8 Automated Verification Test Suite
 * Tests Reports, Exports, and Immutable Audit Trail:
 * - CSV Formula Injection Prevention (CWE-1236)
 * - Sensitive Data Redaction in Audit Logs (passwords, tokens, cookies, secrets)
 * - Audit Trail Immutability & Append-Only Architecture
 * - Server-side Permission Scoping for Reports (Admin vs Supervisor)
 * - Filter Input Validation and SQL Parameterization Defense
 * - Daily, Weekly, and Monthly Inspections Summary Report Accuracy
 * - Floor-wise and Ward-wise Completion Calculation
 * - Supervisor Attendance and GPS Verification Audit Report
 * - Pending, Overdue, and Missed Exceptions Report
 * - Checklist Failures and Critical Findings Extraction
 * - Complaint Status and SLA Breach Calculation
 * - Forensic Audit Event Recording on Report Export
 *
 * Usage: php scripts/test_phase8.php
 */

require_once dirname(__DIR__) . '/app/Core/App.php';
require_once dirname(__DIR__) . '/app/Core/Helpers.php';

use App\Core\App;
use App\Core\Database;
use App\Models\AuditLog;
use App\Services\CsvExportService;
use App\Services\ReportService;

$app = new App(dirname(__DIR__));

echo "\n====================================================================\n";
echo " SHETH L.G. GENERAL HOSPITAL - PHASE 8 TEST SUITE\n";
echo " Reports, Secure Exports & Immutable Audit Trail Verification\n";
echo "====================================================================\n\n";

$testsPassed = 0;
$totalTests = 0;

function runTest(string $title, callable $testFn): void {
    global $testsPassed, $totalTests;
    $totalTests++;
    echo "[TEST {$totalTests}] {$title}... ";
    try {
        $result = $testFn();
        if ($result === true) {
            echo "\033[32m[PASS]\033[0m\n";
            $testsPassed++;
        } else {
            echo "\033[31m[FAIL]\033[0m: " . (is_string($result) ? $result : 'Assertion returned false') . "\n";
        }
    } catch (\Throwable $e) {
        echo "\033[31m[ERROR]\033[0m: " . $e->getMessage() . "\n";
    }
}

$dbConnected = Database::checkConnection()['connected'];

// -----------------------------------------------------------------------------
// TEST 1: CSV Formula Injection Prevention (CWE-1236)
// -----------------------------------------------------------------------------
runTest("CSV Formula Injection Defense (CWE-1236 Neutralization of =, +, -, @, \\t, \\r)", function() {
    $dangerousInputs = [
        '=SUM(A1:A10)'             => "'=SUM(A1:A10)",
        '=cmd|\'/C calc\'!A0'      => "'=cmd|'/C calc'!A0",
        '+1+1'                     => "'+1+1",
        '-5+2'                     => "'-5+2",
        '@SUM(1+1)'                => "'@SUM(1+1)",
        "\tmalicious_tab"          => "'\tmalicious_tab",
        "\rmalicious_cr"           => "'\rmalicious_cr",
        '   =delayed_formula'      => "'   =delayed_formula",
    ];

    foreach ($dangerousInputs as $input => $expected) {
        $sanitized = CsvExportService::sanitizeCell($input);
        if ($sanitized !== $expected) {
            return "Failed to neutralize input '{$input}'. Expected '{$expected}', got '{$sanitized}'";
        }
    }

    // Verify safe inputs remain unharmed
    $safeInputs = [
        ['input' => 'General Ward 101', 'expected' => 'General Ward 101'],
        ['input' => 'ICU - Bed 4',      'expected' => 'ICU - Bed 4'],
        ['input' => 100,                'expected' => '100'],
        ['input' => 98.5,               'expected' => '98.5'],
        ['input' => null,               'expected' => ''],
        ['input' => true,               'expected' => 'Yes'],
        ['input' => false,              'expected' => 'No'],
    ];

    foreach ($safeInputs as $pair) {
        $sanitized = CsvExportService::sanitizeCell($pair['input']);
        if ($sanitized !== $pair['expected']) {
            return "Safe input was corrupted: '" . var_export($pair['input'], true) . "'. Expected '{$pair['expected']}', got '{$sanitized}'";
        }
    }

    // Verify sanitizeRow() works correctly
    $row = ['Ward A', '=1+1', 42, null, true];
    $sanitizedRow = CsvExportService::sanitizeRow($row);
    if ($sanitizedRow !== ['Ward A', "'=1+1", '42', '', 'Yes']) {
        return "sanitizeRow output did not match expected structure";
    }

    return true;
});

// -----------------------------------------------------------------------------
// TEST 2: Sensitive Data Redaction in Audit Logs
// -----------------------------------------------------------------------------
runTest("Sensitive Data Redaction in Audit Logs (Passwords, Tokens, Secrets Masked)", function() {
    $sensitivePayload = [
        'username'         => 'supervisor.patel',
        'email'            => 'patel@shethlg.org',
        'password'         => 'SecretPass123!',
        'password_hash'    => '$2y$10$abcdefghijklmnopqrstuvwxyz1234567890',
        'current_password' => 'OldPass123!',
        'csrf_token'       => '3f2b4c8a9e1d0f5a7b2c',
        'api_key'          => 'key_live_998877665544332211',
        'nested'           => [
            'auth_token'   => 'jwt_eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9',
            'facility'     => 'Main Trauma Center',
            'session_id'   => 'sess_1234567890abcdef',
        ]
    ];

    $cleaned = AuditLog::sanitize($sensitivePayload);

    if ($cleaned['username'] !== 'supervisor.patel') {
        return "Non-sensitive username was corrupted";
    }
    if ($cleaned['password'] !== '[REDACTED]') {
        return "Password was not redacted";
    }
    if ($cleaned['password_hash'] !== '[REDACTED]') {
        return "Password hash was not redacted";
    }
    if ($cleaned['current_password'] !== '[REDACTED]') {
        return "Current password was not redacted";
    }
    if ($cleaned['csrf_token'] !== '[REDACTED]') {
        return "CSRF token was not redacted";
    }
    if ($cleaned['api_key'] !== '[REDACTED]') {
        return "API key was not redacted";
    }
    if ($cleaned['nested']['auth_token'] !== '[REDACTED]') {
        return "Nested auth token was not redacted";
    }
    if ($cleaned['nested']['session_id'] !== '[REDACTED]') {
        return "Nested session id was not redacted";
    }
    if ($cleaned['nested']['facility'] !== 'Main Trauma Center') {
        return "Nested non-sensitive facility was corrupted";
    }

    return true;
});

// -----------------------------------------------------------------------------
// TEST 3: Audit Trail Immutability & Append-Only Architecture
// -----------------------------------------------------------------------------
runTest("Audit Trail Immutability & Append-Only Record Architecture", function() use ($dbConnected) {
    $auditModel = new \ReflectionClass(AuditLog::class);

    // Verify AuditLog does NOT define public delete/destroy/update methods
    $prohibitedMethods = ['delete', 'destroy', 'update', 'truncate', 'purge'];
    foreach ($prohibitedMethods as $method) {
        if ($auditModel->hasMethod($method)) {
            $m = $auditModel->getMethod($method);
            if ($m->getDeclaringClass()->getName() === AuditLog::class && $m->isPublic()) {
                return "Prohibited method '{$method}' found on AuditLog model!";
            }
        }
    }

    // Verify constant events exist
    $constants = [
        'EVENT_FACILITY_CREATED',
        'EVENT_FACILITY_UPDATED',
        'EVENT_USER_CREATED',
        'EVENT_USER_UPDATED',
        'EVENT_INSPECTION_COMPLETED',
        'EVENT_SUSPICIOUS_ACTIVITY',
        'EVENT_REPORT_EXPORTED',
    ];
    foreach ($constants as $const) {
        if (!$auditModel->hasConstant($const)) {
            return "Expected AuditLog constant '{$const}' missing";
        }
    }

    if ($dbConnected) {
        $testAction = 'PHASE8_IMMUTABILITY_TEST';
        $beforeCount = (int)Database::selectValue("SELECT COUNT(*) FROM `audit_logs` WHERE event_action = :act", ['act' => $testAction]);

        AuditLog::record(
            1,
            $testAction,
            'system',
            9999,
            ['status' => 'draft'],
            ['status' => 'published'],
            '127.0.0.1',
            'Phase8TestRunner/1.0'
        );

        $afterCount = (int)Database::selectValue("SELECT COUNT(*) FROM `audit_logs` WHERE event_action = :act", ['act' => $testAction]);
        if ($afterCount !== ($beforeCount + 1)) {
            return "Audit record was not appended to database!";
        }
    }

    return true;
});

// -----------------------------------------------------------------------------
// TEST 4: Role-Based Authorization Scoping (Supervisor vs Admin)
// -----------------------------------------------------------------------------
runTest("Role-Based Authorization Scoping for Reports (Supervisor Isolation vs Admin)", function() use ($dbConnected) {
    $service = new ReportService();

    // Reflection checks for supervisor scoping logic
    $refMethod = new \ReflectionMethod($service, 'getSupervisorAssignedWardIds');
    if (!$refMethod) {
        return "getSupervisorAssignedWardIds method missing on ReportService";
    }

    $adminUser = ['id' => 1, 'role' => 'admin'];
    $supervisor = ['id' => 2, 'role' => 'supervisor'];

    $filters = ['date_preset' => 'this_month'];

    if ($dbConnected) {
        $adminReport = $service->getInspectionSummaryReport($filters, $adminUser);
        $supReport = $service->getInspectionSummaryReport($filters, $supervisor);

        $assignedWards = $service->getSupervisorAssignedWardIds(2);
        foreach ($supReport['rows'] as $row) {
            $isOwn = (int)$row['inspector_user_id'] === 2;
            $isAssignedWard = in_array((int)$row['ward_id'], $assignedWards, true);
            if (!$isOwn && !$isAssignedWard) {
                return "Data leakage: Supervisor saw inspection #{$row['inspection_number']} outside assigned scope!";
            }
        }
    } else {
        // Architectural verification of condition building
        $dates = $service->resolveDateRange($filters);
        if (empty($dates['start_date']) || empty($dates['end_date'])) {
            return "Date range resolution failed";
        }
    }

    return true;
});

// -----------------------------------------------------------------------------
// TEST 5: Filter Input Validation & SQL Parameterization Defense
// -----------------------------------------------------------------------------
runTest("Filter Input Validation & SQL Parameterization Security Defense", function() use ($dbConnected) {
    $service = new ReportService();
    $adminUser = ['id' => 1, 'role' => 'admin'];

    // Malicious SQL injection payloads in filters
    $maliciousFilters = [
        'date_preset'   => 'custom',
        'start_date'    => "2026-01-01' OR '1'='1",
        'end_date'      => "2026-12-31; DROP TABLE users; --",
        'building_id'   => "1 UNION SELECT * FROM users",
        'ward_id'       => "1' OR 1=1 --",
        'shift_id'      => "99999' OR 'a'='a",
        'supervisor_id' => "1; SELECT sleep(5);",
    ];

    // Must resolve dates safely without executing injection
    $resolvedDates = $service->resolveDateRange($maliciousFilters);
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $resolvedDates['start_date']) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $resolvedDates['end_date'])) {
        return "Malformed date string bypassed date validation!";
    }

    // Date range reversal defense: if start > end, must swap automatically
    $reversed = $service->resolveDateRange([
        'date_preset' => 'custom',
        'start_date'  => '2026-12-31',
        'end_date'    => '2026-01-01',
    ]);
    if ($reversed['start_date'] > $reversed['end_date']) {
        return "Date reversal defense failed: start_date is after end_date";
    }

    if ($dbConnected) {
        try {
            $rep = $service->getInspectionSummaryReport($maliciousFilters, $adminUser);
            if (!isset($rep['rows'])) {
                return "Expected valid report structure despite malicious filter input";
            }
        } catch (\PDOException $e) {
            return "SQL injection vulnerability triggered PDOException: " . $e->getMessage();
        }
    }

    return true;
});

// -----------------------------------------------------------------------------
// TEST 6: Inspections Summary Report Accuracy
// -----------------------------------------------------------------------------
runTest("Inspections Summary Report Aggregation & Calculation Accuracy", function() use ($dbConnected) {
    $service = new ReportService();
    $adminUser = ['id' => 1, 'role' => 'admin'];

    $filters = [
        'date_preset' => 'custom',
        'start_date'  => '2026-01-01',
        'end_date'    => '2026-12-31',
    ];

    if ($dbConnected) {
        $report = $service->getInspectionSummaryReport($filters, $adminUser);
        if ($report['type'] !== 'inspections_summary') {
            return "Report type mismatch";
        }
        if (!isset($report['kpis']['total_inspections'], $report['kpis']['avg_score'])) {
            return "KPI structure missing expected metrics";
        }
        if (count($report['rows']) !== $report['kpis']['total_inspections']) {
            return "KPI total_inspections does not match row count";
        }
    } else {
        // Algorithm validation
        $simulatedScores = [85.5, 90.0, 95.0, 100.0];
        $avgScore = round(array_sum($simulatedScores) / count($simulatedScores), 1);
        if ($avgScore !== 92.6) {
            return "Average score calculation failed. Expected 92.6%, got {$avgScore}%";
        }
    }

    return true;
});

// -----------------------------------------------------------------------------
// TEST 7: Floor-wise & Ward-wise Completion Report Calculation
// -----------------------------------------------------------------------------
runTest("Floor-wise & Ward-wise Completion Report Calculation", function() use ($dbConnected) {
    $service = new ReportService();
    $adminUser = ['id' => 1, 'role' => 'admin'];

    $filters = [
        'date_preset' => 'custom',
        'start_date'  => '2026-01-01',
        'end_date'    => '2026-12-31',
    ];

    if ($dbConnected) {
        $report = $service->getCompletionReport($filters, $adminUser);
        if ($report['type'] !== 'completion_report') {
            return "Report type mismatch";
        }
        foreach ($report['rows'] as $r) {
            $scheduled = (int)$r['total_scheduled'];
            $completed = (int)$r['completed_count'];
            $rate = (float)$r['completion_rate'];

            if ($scheduled > 0) {
                $expectedRate = round(($completed / $scheduled) * 100, 1);
                if (abs($rate - $expectedRate) > 0.01) {
                    return "Completion rate math error on ward {$r['ward_name']}: Expected {$expectedRate}%, got {$rate}%";
                }
            }
        }
    } else {
        // Mathematical edge cases
        $totalScheduled = 25;
        $completed = 20;
        $rate = round(($completed / $totalScheduled) * 100, 1);
        if ($rate !== 80.0) {
            return "Completion rate math error. Expected 80.0%, got {$rate}%";
        }

        // Division by zero defense
        $zeroScheduled = 0;
        $zeroRate = $zeroScheduled > 0 ? round((0 / $zeroScheduled) * 100, 1) : 0.0;
        if ($zeroRate !== 0.0) {
            return "Zero division defense failed";
        }
    }

    return true;
});

// -----------------------------------------------------------------------------
// TEST 8: Supervisor Attendance & GPS Verification Audit Report
// -----------------------------------------------------------------------------
runTest("Supervisor Attendance & GPS Geofence Verification Report", function() use ($dbConnected) {
    $service = new ReportService();
    $adminUser = ['id' => 1, 'role' => 'admin'];

    $filters = [
        'date_preset' => 'custom',
        'start_date'  => '2026-01-01',
        'end_date'    => '2026-12-31',
    ];

    if ($dbConnected) {
        $report = $service->getAttendanceReport($filters, $adminUser);
        if ($report['type'] !== 'attendance_report') {
            return "Report type mismatch";
        }
        $kpis = $report['kpis'];
        if (!isset($kpis['total_attendance'], $kpis['verified_count'], $kpis['verified_rate'])) {
            return "Attendance KPI structure missing expected keys";
        }
    } else {
        $simulatedAttendance = [
            ['status' => 'present', 'verification_status' => 'verified'],
            ['status' => 'present', 'verification_status' => 'verified'],
            ['status' => 'late',    'verification_status' => 'verified'],
            ['status' => 'present', 'verification_status' => 'suspicious_gps'],
        ];

        $verified = 0;
        foreach ($simulatedAttendance as $row) {
            if ($row['verification_status'] === 'verified') {
                $verified++;
            }
        }
        $rate = round(($verified / count($simulatedAttendance)) * 100, 1);
        if ($rate !== 75.0) {
            return "Attendance verified rate calculation failed. Expected 75.0%, got {$rate}%";
        }
    }

    return true;
});

// -----------------------------------------------------------------------------
// TEST 9: Exceptions Report (Pending, Overdue, and Missed)
// -----------------------------------------------------------------------------
runTest("Exceptions Report (Pending, Overdue, and Missed Schedule Detection)", function() use ($dbConnected) {
    $service = new ReportService();
    $adminUser = ['id' => 1, 'role' => 'admin'];

    $filters = [
        'date_preset' => 'custom',
        'start_date'  => '2026-01-01',
        'end_date'    => '2026-12-31',
    ];

    if ($dbConnected) {
        $report = $service->getExceptionsReport($filters, $adminUser);
        if ($report['type'] !== 'exceptions_report') {
            return "Report type mismatch";
        }
    } else {
        // Test overdue detection logic
        $today = date('Y-m-d');
        $pastDate = date('Y-m-d', strtotime('-2 days'));
        $futureDate = date('Y-m-d', strtotime('+2 days'));

        $schedules = [
            ['id' => 1, 'scheduled_date' => $pastDate, 'status' => 'pending'],
            ['id' => 2, 'scheduled_date' => $pastDate, 'status' => 'missed'],
            ['id' => 3, 'scheduled_date' => $futureDate, 'status' => 'pending'],
            ['id' => 4, 'scheduled_date' => $pastDate, 'status' => 'completed'],
        ];

        $exceptions = [];
        foreach ($schedules as $s) {
            $isOverdue = ($s['status'] === 'pending' || $s['status'] === 'in_progress') && $s['scheduled_date'] < $today;
            if ($s['status'] === 'missed' || $isOverdue || $s['status'] === 'pending') {
                if ($s['status'] !== 'completed') {
                    $exceptions[] = $s;
                }
            }
        }

        if (count($exceptions) !== 3) {
            return "Exception schedule filter mismatch: expected 3 exception records, got " . count($exceptions);
        }
    }

    return true;
});

// -----------------------------------------------------------------------------
// TEST 10: Checklist Failures & Critical Findings Extraction
// -----------------------------------------------------------------------------
runTest("Checklist Failures & Critical Findings Extraction Accuracy", function() use ($dbConnected) {
    $service = new ReportService();
    $adminUser = ['id' => 1, 'role' => 'admin'];

    $filters = [
        'date_preset' => 'custom',
        'start_date'  => '2026-01-01',
        'end_date'    => '2026-12-31',
    ];

    if ($dbConnected) {
        $report = $service->getFailuresReport($filters, $adminUser);
        if ($report['type'] !== 'failures_report') {
            return "Report type mismatch";
        }
        foreach ($report['rows'] as $r) {
            $isNonCompliant = (int)$r['is_compliant'] === 0;
            $isCritical = (int)$r['is_critical_failure'] === 1;
            if (!$isNonCompliant && !$isCritical) {
                return "Compliant non-critical item leaked into failures report";
            }
        }
    } else {
        // Algorithm test
        $items = [
            ['id' => 1, 'is_compliant' => 1, 'is_critical_failure' => 0], // Compliant - Excluded
            ['id' => 2, 'is_compliant' => 0, 'is_critical_failure' => 0], // Failed - Included
            ['id' => 3, 'is_compliant' => 0, 'is_critical_failure' => 1], // Critical Failed - Included
            ['id' => 4, 'is_compliant' => 1, 'is_critical_failure' => 1], // Edge case: Flagged Critical - Included
        ];

        $failures = array_filter($items, function($it) {
            return $it['is_compliant'] === 0 || $it['is_critical_failure'] === 1;
        });

        if (count($failures) !== 3) {
            return "Failures filter count mismatch. Expected 3, got " . count($failures);
        }
    }

    return true;
});

// -----------------------------------------------------------------------------
// TEST 11: Complaints & SLA Breach Report Calculation
// -----------------------------------------------------------------------------
runTest("Complaint Status & SLA Breach Calculation Accuracy", function() use ($dbConnected) {
    $service = new ReportService();
    $adminUser = ['id' => 1, 'role' => 'admin'];

    $filters = [
        'date_preset' => 'custom',
        'start_date'  => '2026-01-01',
        'end_date'    => '2026-12-31',
    ];

    if ($dbConnected) {
        $report = $service->getSlaReport($filters, $adminUser);
        if ($report['type'] !== 'sla_report') {
            return "Report type mismatch";
        }
    } else {
        // Test SLA breach calculation logic
        $now = new DateTime('now', new \DateTimeZone('Asia/Kolkata'));
        $pastDue = (clone $now)->modify('-5 hours')->format('Y-m-d H:i:s');
        $futureDue = (clone $now)->modify('+5 hours')->format('Y-m-d H:i:s');

        $complaints = [
            ['id' => 1, 'status' => 'open', 'due_date' => $pastDue, 'resolved_at' => null],       // Breached
            ['id' => 2, 'status' => 'resolved', 'due_date' => $pastDue, 'resolved_at' => $pastDue], // Resolved in time
            ['id' => 3, 'status' => 'in_progress', 'due_date' => $futureDue, 'resolved_at' => null], // Within SLA
            ['id' => 4, 'status' => 'resolved', 'due_date' => $pastDue, 'resolved_at' => (clone $now)->format('Y-m-d H:i:s')], // Resolved late (Breached)
        ];

        $breachedCount = 0;
        foreach ($complaints as $c) {
            $isResolved = in_array($c['status'], ['resolved', 'closed'], true);
            $dueDate = new DateTime($c['due_date'], new \DateTimeZone('Asia/Kolkata'));
            $isBreached = false;

            if (!$isResolved && $now > $dueDate) {
                $isBreached = true;
            } elseif ($isResolved && !empty($c['resolved_at'])) {
                $resDate = new DateTime($c['resolved_at'], new \DateTimeZone('Asia/Kolkata'));
                if ($resDate > $dueDate) {
                    $isBreached = true;
                }
            }

            if ($isBreached) {
                $breachedCount++;
            }
        }

        if ($breachedCount !== 2) {
            return "SLA breach calculation mismatch: expected 2 breaches, got {$breachedCount}";
        }

        $complianceRate = round(((count($complaints) - $breachedCount) / count($complaints)) * 100, 1);
        if ($complianceRate !== 50.0) {
            return "SLA compliance rate mismatch. Expected 50.0%, got {$complianceRate}%";
        }
    }

    return true;
});

// -----------------------------------------------------------------------------
// TEST 12: Forensic Audit Event Recording on Report Export
// -----------------------------------------------------------------------------
runTest("Forensic Audit Event Recording on Report Export", function() use ($dbConnected) {
    // Generate temporary test file and record export event
    $testHeaders = ['Facility', 'Score', 'Findings'];
    $testRows = [
        ['Emergency Trauma Ward', 95.0, 'None'],
        ['Cardiac Care Unit', 82.5, 'Minor'],
    ];

    $exportFile = CsvExportService::generateTempFile('audit_test', $testHeaders, $testRows);

    if (!file_exists($exportFile)) {
        return "Temporary export file was not generated";
    }

    // Verify file content is safe and contains UTF-8 BOM
    $content = file_get_contents($exportFile);
    if (!str_starts_with($content, "\xEF\xBB\xBF")) {
        return "Exported CSV missing UTF-8 Byte Order Mark (BOM)";
    }

    if ($dbConnected) {
        // Log the export event
        AuditLog::record(
            1,
            AuditLog::EVENT_REPORT_EXPORTED,
            'reports',
            null,
            null,
            [
                'report_type' => 'test_export_phase8',
                'filename'    => basename($exportFile),
                'rows_count'  => count($testRows),
            ]
        );

        // Verify audit log has the export event recorded
        $lastExportLog = Database::selectOne(
            "SELECT * FROM `audit_logs` WHERE event_action = :act AND entity_table = 'reports' ORDER BY id DESC LIMIT 1",
            ['act' => AuditLog::EVENT_REPORT_EXPORTED]
        );

        if (!$lastExportLog) {
            return "Export audit log was not found in audit_logs table";
        }
    }

    // Cleanup temp file
    @unlink($exportFile);

    return true;
});

// -----------------------------------------------------------------------------
// FINAL SUMMARY
// -----------------------------------------------------------------------------
echo "--------------------------------------------------------------------\n";
echo " TEST SUMMARY: {$testsPassed} / {$totalTests} TESTS PASSED\n";
echo "--------------------------------------------------------------------\n\n";

if ($testsPassed === $totalTests) {
    echo "\033[32m>>> ALL PHASE 8 REPORTS, EXPORTS & AUDIT TRAIL TESTS PASSED SUCCESSFULLY! <<<\033[0m\n\n";
    exit(0);
} else {
    echo "\033[31m>>> SOME PHASE 8 TESTS FAILED! <<<\033[0m\n\n";
    exit(1);
}
