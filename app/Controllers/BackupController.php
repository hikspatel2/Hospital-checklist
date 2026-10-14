<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Models\AuditLog;
use App\Models\HospitalSetting;
use App\Models\User;
use App\Services\BackupService;
use Throwable;

/**
 * Secure Backup & Disaster Recovery Console (Admin Only)
 * Manages automated & manual backups, SHA-256 checksum verification,
 * dry-run restore tests, disaster recovery, and grandfather-father-son retention.
 */
class BackupController extends Controller
{
    private BackupService $backupService;

    public function __construct()
    {
        $this->backupService = new BackupService();
    }

    /**
     * Backup Dashboard Console
     */
    public function index(Request $request): Response
    {
        $diskBackups = $this->backupService->scanBackupsOnDisk();

        // Retrieve database backup history
        $dbLogs = [];
        $restoreLogs = [];
        try {
            $dbLogs = Database::selectAll(
                "SELECT bl.*, u.name as user_name 
                 FROM `backup_logs` bl 
                 LEFT JOIN `users` u ON bl.user_id = u.id 
                 ORDER BY bl.created_at DESC LIMIT 15"
            );
            $restoreLogs = Database::selectAll(
                "SELECT rl.*, u.name as user_name 
                 FROM `restore_logs` rl 
                 LEFT JOIN `users` u ON rl.initiated_by = u.id 
                 ORDER BY rl.created_at DESC LIMIT 10"
            );
        } catch (Throwable) {}

        // Compute backup health metrics
        $latestBackupTime = !empty($diskBackups) ? $diskBackups[0]['timestamp'] : null;
        $isStale = true;
        if ($latestBackupTime) {
            $hoursAgo = (time() - strtotime($latestBackupTime)) / 3600;
            $isStale = ($hoursAgo > 24);
        }

        $totalBytes = array_sum(array_column($diskBackups, 'size'));

        $lastRestoreTest = null;
        foreach ($dbLogs as $log) {
            if (!empty($log['restore_tested_at'])) {
                $lastRestoreTest = $log['restore_tested_at'];
                break;
            }
        }

        $retentionSettings = [
            'daily_days'    => (int)HospitalSetting::get('backup_retention_days', '7'),
            'weekly_weeks'  => 4,
            'monthly_months'=> 3,
        ];

        return $this->render('backups/index', [
            'pageTitle'        => 'Backup & Disaster Recovery - Sheth L.G. General Hospital',
            'diskBackups'      => $diskBackups,
            'dbLogs'           => $dbLogs,
            'restoreLogs'      => $restoreLogs,
            'latestBackupTime' => $latestBackupTime,
            'isStale'          => $isStale,
            'totalStorage'     => $this->backupService->formatBytes($totalBytes),
            'totalCount'       => count($diskBackups),
            'lastRestoreTest'  => $lastRestoreTest,
            'retention'        => $retentionSettings,
        ], 'layouts/main');
    }

    /**
     * Create Backup Now (Manual Trigger)
     */
    public function create(Request $request): void
    {
        $user = Auth::user();
        $archiveType = (string)$request->input('archive_type', 'database_only');
        $encrypt = (bool)$request->input('encrypt', 1);

        if (!in_array($archiveType, ['database_only', 'full_with_evidence'], true)) {
            $archiveType = 'database_only';
        }

        $result = $this->backupService->createBackup($archiveType, $encrypt, 'manual', (int)$user['id']);

        if (!$result['success']) {
            $this->flash('error', "<strong>Backup Failed:</strong> " . e($result['error']));
        } else {
            $encBadge = $result['is_encrypted'] ? ' (AES-256 Encrypted)' : '';
            $this->flash('success', "<strong>Backup Successful:</strong> Generated <code>{$result['filename']}</code> ({$result['size_formatted']}, {$result['tables_count']} tables){$encBadge}. Integrity verified.");
        }

        $this->redirect('/backups');
    }

    /**
     * Download Backup Archive (Secure Streamed Transfer)
     */
    public function download(Request $request, ?string $filename = null): void
    {
        $rawFilename = $filename ?? (string)$request->param('filename', '');
        $filename = basename($rawFilename); // Prevent path traversal (CWE-22)

        $base = defined('ROOT_PATH') ? \ROOT_PATH : dirname(__DIR__, 2);
        $storageDir = $base . '/storage/backups';
        $filePath = $storageDir . '/' . $filename;

        if (!file_exists($filePath) || !is_file($filePath)) {
            $this->flash('error', "Backup archive file not found.");
            $this->redirect('/backups');
            return;
        }

        $user = Auth::user();
        AuditLog::record(
            (int)$user['id'],
            'BACKUP_ARCHIVE_DOWNLOADED',
            'backup_logs',
            null,
            null,
            ['file' => $filename, 'size' => filesize($filePath)],
            $request->getIp(),
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        );

        $mime = 'application/octet-stream';
        if (str_ends_with($filename, '.sql')) {
            $mime = 'application/sql';
        } elseif (str_ends_with($filename, '.gz')) {
            $mime = 'application/gzip';
        } elseif (str_ends_with($filename, '.zip')) {
            $mime = 'application/zip';
        }

        Response::file($filePath, $mime, $filename, true);
    }

    /**
     * Verify Checksum against Database Signature
     */
    public function verify(Request $request, ?string $filename = null): void
    {
        $filename = basename($filename ?? (string)$request->param('filename', ''));
        $result = $this->backupService->verifyChecksum($filename);

        if (!$result['valid']) {
            $this->flash('error', "<strong>Integrity Alert:</strong> Checksum verification failed for <code>{$filename}</code>! File may be corrupt or altered.");
        } else {
            $this->flash('success', "<strong>Integrity Verified:</strong> <code>{$filename}</code> matches recorded SHA-256 signature (<code>" . substr($result['calculated_hash'], 0, 16) . "...</code>).");
        }

        $this->redirect('/backups');
    }

    /**
     * Test Restore Dry-Run
     */
    public function testRestore(Request $request, ?string $filename = null): void
    {
        $filename = basename($filename ?? (string)$request->param('filename', ''));
        $result = $this->backupService->testRestoreDryRun($filename);

        if (!$result['success']) {
            $err = $result['error'] ?? 'Schema verification failed.';
            $this->flash('error', "<strong>Dry-Run Test Failed:</strong> " . e($err));
        } else {
            $tables = count($result['verified_tables']);
            $this->flash('success', "<strong>Dry-Run Restore Passed:</strong> Verified {$tables} core hospital tables, syntax, and data blocks in <code>{$filename}</code>. Live database remained untouched.");
        }

        $this->redirect('/backups');
    }

    /**
     * Execute Live Disaster Restoration (Requires Admin Re-Authentication!)
     */
    public function restore(Request $request, ?string $filename = null): void
    {
        $user = Auth::user();
        $filename = basename($filename ?? (string)$request->param('filename', ''));
        $confirmPassword = (string)$request->input('admin_password', '');

        // 1. Mandatory Re-Authentication Guard
        $userModel = new User();
        $dbUser = $userModel->find((int)$user['id']);

        if (!$dbUser || !password_verify($confirmPassword, $dbUser['password_hash'])) {
            AuditLog::record(
                (int)$user['id'],
                'RESTORE_AUTH_FAILED',
                'system',
                null,
                null,
                ['attempted_archive' => $filename],
                $request->getIp(),
                $_SERVER['HTTP_USER_AGENT'] ?? ''
            );

            $this->flash('error', 'Restoration cancelled: Incorrect administrator confirmation password.');
            $this->redirect('/backups');
            return;
        }

        // 2. Execute Restoration Workflow
        $result = $this->backupService->executeRestore($filename, (int)$user['id'], 'production');

        if (!$result['success']) {
            $pre = e($result['pre_restore_backup'] ?? 'Unknown');
            $this->flash('error', "<strong>Restoration Failed:</strong> " . e($result['error']) . "<br>Safety pre-restore backup is available at <code>{$pre}</code>.");
        } else {
            $this->flash('success', "<strong>Disaster Restoration Complete:</strong> Successfully restored <code>{$filename}</code> ({$result['statements_executed']} statements executed in {$result['duration_sec']}s). Pre-restore safety snapshot created: <code>{$result['pre_restore_backup']}</code>.");
        }

        $this->redirect('/backups');
    }

    /**
     * Execute Retention Policy Cleanup
     */
    public function cleanup(Request $request): void
    {
        $result = $this->backupService->applyRetention();

        if ($result['purged_count'] === 0) {
            $this->flash('info', "Retention Cleanup: No stale backups eligible for purging. {$result['retained_count']} archives retained under policy.");
        } else {
            $this->flash('success', "Retention Cleanup: Purged {$result['purged_count']} stale archives. {$result['retained_count']} archives retained under Grandfather-Father-Son policy.");
        }

        $this->redirect('/backups');
    }
}
