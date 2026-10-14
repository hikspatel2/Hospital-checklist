<?php
declare(strict_types=1);

/**
 * Hostinger-Compatible Automated Backup CLI Runner
 * Sheth L.G. General Hospital, Ahmedabad
 *
 * Usage:
 *   php scripts/backup.php [database_only|full_with_evidence] [--no-encrypt]
 *
 * Cron Pattern (Hostinger cPanel / hPanel):
 *   0 2 * * * /usr/bin/php /home/u123456789/domains/shethlg.com/public_html/../scripts/backup.php >> /dev/null 2>&1
 */

require_once dirname(__DIR__) . '/app/Core/App.php';
require_once dirname(__DIR__) . '/app/Core/Helpers.php';

use App\Core\App;
use App\Services\BackupService;

$app = new App(dirname(__DIR__));

echo "\n====================================================================\n";
echo " SHETH L.G. GENERAL HOSPITAL - AUTOMATED BACKUP ENGINE\n";
echo " Hostinger Shared Hosting Compatible Pure-PHP Stream Architecture\n";
echo "====================================================================\n\n";

$archiveType = $argv[1] ?? 'database_only';
if (!in_array($archiveType, ['database_only', 'full_with_evidence'], true)) {
    $archiveType = 'database_only';
}

$encrypt = !in_array('--no-encrypt', $argv, true);

echo "Executing {$archiveType} backup (Encryption: " . ($encrypt ? 'AES-256-CBC' : 'Disabled') . ")...\n";

$backupService = new BackupService();
$result = $backupService->createBackup($archiveType, $encrypt, 'cron', null);

if (!$result['success']) {
    echo "\033[31m[FAILED]\033[0m Backup generation failed: " . ($result['error'] ?? 'Unknown error') . "\n\n";
    exit(1);
}

echo "\033[32m[SUCCESS]\033[0m Backup archive created successfully!\n";
echo " - File Name:     {$result['filename']}\n";
echo " - Size:          {$result['size_formatted']} ({$result['size_bytes']} bytes)\n";
echo " - SHA-256 Hash:  {$result['sha256']}\n";
echo " - Tables Dumped: {$result['tables_count']}\n";
echo " - Rows Dumped:   {$result['records_count']}\n";
echo " - Duration:      {$result['duration_sec']}s\n";
echo " - Encrypted:     " . ($result['is_encrypted'] ? 'Yes (Authenticated HMAC-SHA256)' : 'No') . "\n";
echo " - Storage:       Restricted (storage/backups/)\n\n";

// Automatically apply retention cleanup after backup
echo "Running retention policy cleanup...\n";
$retention = $backupService->applyRetention();
echo " - Retained: {$retention['retained_count']} archives\n";
echo " - Purged:   {$retention['purged_count']} stale archives\n\n";

exit(0);
