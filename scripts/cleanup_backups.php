<?php
declare(strict_types=1);

/**
 * Hostinger-Compatible Retention Policy Cleanup CLI Runner
 * Sheth L.G. General Hospital, Ahmedabad
 *
 * Usage:
 *   php scripts/cleanup_backups.php
 *
 * Cron Pattern (Hostinger cPanel / hPanel):
 *   0 3 * * * /usr/bin/php /home/u123456789/domains/shethlg.com/public_html/../scripts/cleanup_backups.php >> /dev/null 2>&1
 */

require_once dirname(__DIR__) . '/app/Core/App.php';
require_once dirname(__DIR__) . '/app/Core/Helpers.php';

use App\Core\App;
use App\Services\BackupService;

$app = new App(dirname(__DIR__));

echo "\n====================================================================\n";
echo " SHETH L.G. GENERAL HOSPITAL - RETENTION POLICY CLEANUP ENGINE\n";
echo " Grandfather-Father-Son (GFS): 7 Daily | 4 Weekly | 3 Monthly\n";
echo "====================================================================\n\n";

$backupService = new BackupService();
$result = $backupService->applyRetention();

echo "Retention Cleanup Execution Summary:\n";
echo " - Total Archives Found: " . ($result['retained_count'] + $result['purged_count']) . "\n";
echo " - Archives Retained:     {$result['retained_count']}\n";
echo " - Archives Purged:       {$result['purged_count']}\n\n";

if (!empty($result['purged_files'])) {
    echo "Purged Archives:\n";
    foreach ($result['purged_files'] as $file) {
        echo "   * {$file}\n";
    }
    echo "\n";
} else {
    echo "No stale archives required purging. All archives comply with GFS policy.\n\n";
}

echo "\033[32m[SUCCESS]\033[0m Retention policy verification completed successfully.\n\n";
exit(0);
