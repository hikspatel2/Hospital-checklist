<?php
declare(strict_types=1);

/**
 * Sheth L.G. General Hospital, Ahmedabad
 * Automated Inspection Schedule Generator & Overdue State Monitor
 *
 * Designed for standard Hostinger cPanel / hPanel Cron Jobs:
 * Command: /usr/bin/php /home/u123456789/hospital_app/scripts/cron_generate_schedules.php > /dev/null 2>&1
 * Schedule: Daily at 01:00 AM IST (or every hour for shift updates)
 */

require_once dirname(__DIR__) . '/app/Core/App.php';
require_once dirname(__DIR__) . '/app/Core/Helpers.php';

use App\Core\App;
use App\Core\Database;
use App\Models\AuditLog;
use App\Services\InspectionScheduleService;

$app = new App(dirname(__DIR__));

echo "[" . date('Y-m-d H:i:s') . "] Starting Sheth L.G. General Hospital Inspection Schedule Cron Engine...\n";

// Target date: command line argument, or today
$targetDate = $argv[1] ?? date('Y-m-d');
$daysAhead = 7; // Generate 7 days in advance

try {
    $daysSetting = Database::selectOne("SELECT `setting_value` FROM `hospital_settings` WHERE `setting_key` = 'schedule_generation_days_ahead' LIMIT 1");
    if ($daysSetting) {
        $daysAhead = max(1, (int)$daysSetting['setting_value']);
    }
} catch (\Throwable $e) {
    // Default fallback
}

// 1. Update past-due schedules to 'overdue'
$overdueCount = InspectionScheduleService::updateOverdueSchedules();
echo "[" . date('Y-m-d H:i:s') . "] Updated {$overdueCount} past-due schedules to 'overdue'.\n";

// 2. Retry failed schedule generations
$retriedCount = InspectionScheduleService::retryFailedSchedules();
if ($retriedCount > 0) {
    echo "[" . date('Y-m-d H:i:s') . "] Retried {$retriedCount} previously failed schedule generations.\n";
}

// 3. Generate schedules for today through N days ahead (idempotent)
$totalGenerated = 0;
$totalExisting = 0;
$totalErrors = 0;

for ($i = 0; $i < $daysAhead; $i++) {
    $currentDate = date('Y-m-d', strtotime("{$targetDate} +{$i} days"));
    $res = InspectionScheduleService::generateSchedulesForDate($currentDate);

    $totalGenerated += $res['generated'];
    $totalExisting += $res['skipped_existing'];
    $totalErrors += count($res['errors']);

    echo "  -> Date {$currentDate}: {$res['generated']} created, {$res['skipped_existing']} existing, " . count($res['errors']) . " errors.\n";
    if (!empty($res['errors'])) {
        foreach ($res['errors'] as $err) {
            echo "     [ERROR]: {$err}\n";
        }
    }
}

echo "\n--------------------------------------------------------------------\n";
echo " CRON SUMMARY: {$totalGenerated} created, {$totalExisting} existing (idempotent), {$totalErrors} errors\n";
echo "--------------------------------------------------------------------\n";
echo "[" . date('Y-m-d H:i:s') . "] Cron job completed successfully.\n";

exit($totalErrors > 0 ? 1 : 0);
