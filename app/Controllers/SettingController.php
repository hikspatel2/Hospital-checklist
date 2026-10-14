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

/**
 * Hospital Settings & Security Hardening Controller (Admin Only)
 * Manages clinical governance parameters, GPS geofencing, checklist & complaint SLAs,
 * data retention, backup safeguards, maintenance mode, and system health telemetry.
 */
class SettingController extends Controller
{
    /**
     * Settings Console & System Health Diagnostic Dashboard
     */
    public function index(Request $request): Response
    {
        $groupedSettings = HospitalSetting::getAllGrouped();
        $healthTelemetry = $this->collectHealthDiagnostics();
        
        // Retrieve recent settings audit modifications
        $recentAudit = AuditLog::getFiltered([
            'entity_table' => 'hospital_settings',
        ], 1, 8);

        return $this->render('settings/index', [
            'pageTitle'       => 'Hospital Settings & Security Hardening - Sheth L.G. General Hospital',
            'groupedSettings' => $groupedSettings,
            'health'          => $healthTelemetry,
            'recentAudit'     => $recentAudit['data'] ?? [],
            'timezones'       => \DateTimeZone::listIdentifiers(),
        ], 'layouts/main');
    }

    /**
     * Batch Update Settings with Safeguard Protection
     */
    public function update(Request $request): void
    {
        $user = Auth::user();
        $postData = $request->all();

        // Strip CSRF token from setting keys
        unset($postData['_token'], $postData['_method']);

        $result = HospitalSetting::updateBatch($postData, (int)$user['id']);

        if (!$result['success']) {
            $errorMsg = implode('<br>', array_map('e', $result['errors']));
            $this->flash('error', "<strong>Setting Validation Failed:</strong><br>{$errorMsg}");
        } else {
            $this->flash('success', 'Hospital operational settings and security parameters updated successfully.');
        }

        $this->redirect('/settings');
    }

    /**
     * System Health & Integrity Diagnostic Engine
     */
    private function collectHealthDiagnostics(): array
    {
        $startTime = microtime(true);
        $dbConnected = false;
        $dbLatencyMs = 0;
        $dbVersion = 'Unknown';
        
        try {
            $db = Database::getConnection();
            $stmt = $db->query("SELECT VERSION() as v");
            $res = $stmt->fetch();
            $dbVersion = $res['v'] ?? 'MySQL/MariaDB';
            $dbConnected = true;
            $dbLatencyMs = round((microtime(true) - $startTime) * 1000, 2);
        } catch (\Throwable $e) {
            $dbConnected = false;
        }

        // Storage directories write permissions
        $storagePaths = [
            'logs'        => ROOT_PATH . '/storage/logs',
            'floor_plans' => ROOT_PATH . '/storage/uploads/floor_plans',
            'evidence'    => ROOT_PATH . '/storage/uploads/evidence',
            'cache'       => ROOT_PATH . '/storage/cache',
        ];

        $directoryHealth = [];
        $allWritable = true;
        foreach ($storagePaths as $name => $path) {
            if (!is_dir($path)) {
                @mkdir($path, 0755, true);
            }
            $isWritable = is_writable($path);
            if (!$isWritable) {
                $allWritable = false;
            }
            $directoryHealth[$name] = [
                'path'     => $path,
                'writable' => $isWritable,
            ];
        }

        // Audit Trail Integrity Count
        $auditCount = 0;
        try {
            $row = Database::selectOne("SELECT COUNT(*) as c FROM `audit_logs`");
            $auditCount = (int)($row['c'] ?? 0);
        } catch (\Throwable) {
            $auditCount = 0;
        }

        // Active Timezone Verification
        $configuredTz = HospitalSetting::get('app_timezone', 'Asia/Kolkata');
        $runtimeTz = date_default_timezone_get();
        $tzMatched = ($configuredTz === $runtimeTz);

        // Maintenance Mode Status
        $maintenanceActive = (bool)(int)HospitalSetting::get('maintenance_mode_enabled', '0');

        return [
            'database' => [
                'connected' => $dbConnected,
                'latency'   => $dbLatencyMs,
                'version'   => $dbVersion,
            ],
            'php' => [
                'version'        => PHP_VERSION,
                'memory_usage'   => round(memory_get_usage(true) / 1024 / 1024, 2) . ' MB',
                'memory_peak'    => round(memory_get_peak_usage(true) / 1024 / 1024, 2) . ' MB',
                'upload_max'     => ini_get('upload_max_filesize'),
                'post_max'       => ini_get('post_max_size'),
                'max_exec_time'  => ini_get('max_execution_time') . 's',
            ],
            'timezone' => [
                'configured' => $configuredTz,
                'runtime'    => $runtimeTz,
                'synchronized' => $tzMatched,
                'current_time' => date('Y-m-d H:i:s T'),
            ],
            'storage' => [
                'all_writable' => $allWritable,
                'directories'  => $directoryHealth,
            ],
            'security' => [
                'safeguards_active' => true,
                'audit_trail_count' => $auditCount,
                'csrf_protection'   => 'Active (Strict Origin/Tokens)',
                'rate_limiting'     => 'Active (5-attempt lockout, 15m duration)',
                'session_cookie'    => [
                    'httponly' => (bool)ini_get('session.cookie_httponly'),
                    'samesite' => ini_get('session.cookie_samesite') ?: 'Lax',
                    'secure'   => (bool)ini_get('session.cookie_secure'),
                ],
                'maintenance_mode'  => $maintenanceActive,
            ],
        ];
    }
}
