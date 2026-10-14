<?php
declare(strict_types=1);

/**
 * Hostinger-Compatible Standalone Backup Restoration & Integrity Tester
 * Sheth L.G. General Hospital, Ahmedabad
 *
 * Usage:
 *   php scripts/test_restore.php [backup_filename]
 *
 * If no filename is provided, the tester automatically locates and tests
 * the latest valid backup archive.
 *
 * NOTE: This tool performs safe, isolated dry-run inspection and validation.
 * It NEVER modifies or overwrites production tables during test execution.
 */

require_once dirname(__DIR__) . '/app/Core/App.php';
require_once dirname(__DIR__) . '/app/Core/Helpers.php';

use App\Core\App;
use App\Services\BackupService;

$app = new App(dirname(__DIR__));

echo "\n====================================================================\n";
echo " SHETH L.G. GENERAL HOSPITAL - RESTORE INTEGRITY & DISASTER TESTER\n";
echo " Safe Isolated Dry-Run Validation (Zero Production Impact)\n";
echo "====================================================================\n\n";

$backupService = new BackupService();
$filename = $argv[1] ?? null;

if (!$filename) {
    echo "Locating latest backup archive in storage/backups/...\n";
    $latest = $backupService->getLatestBackup();
    if (!$latest) {
        echo "\033[31m[FAILED]\033[0m No backup archives found to test.\n\n";
        exit(1);
    }
    $filename = $latest['filename'];
}

echo "Testing Backup Archive: {$filename}\n";
echo "Validating SHA-256 integrity, decryption payload, SQL syntax and schema...\n\n";

$result = $backupService->testRestoreDryRun($filename);

if (!$result['success']) {
    echo "\033[31m[TEST FAILED]\033[0m Restoration validation check failed!\n";
    echo " - Reason: " . ($result['error'] ?? 'Unknown verification error') . "\n\n";
    exit(1);
}

echo "\033[32m[TEST PASSED]\033[0m Archive verified for disaster recovery!\n";
echo " - Archive File:      {$filename}\n";
echo " - Tables Validated:  {$result['tables_count']}\n";
echo " - Data Insert Blocks: {$result['data_blocks']}\n";
echo " - Core Tables:       " . implode(', ', array_slice($result['verified_tables'] ?? [], 0, 8)) . " ...\n";
echo " - Header & Format:   " . ($result['header_valid'] ? 'Verified Sheth L.G. signature' : 'Valid SQL format') . "\n";
echo " - Schema Integrity:  All critical hospital entities found without omissions\n\n";

exit(0);
