<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use DateTimeZone;

/**
 * Hospital Settings Model
 * Manages facility operational configuration, clinical governance parameters,
 * and critical safeguard defenses against accidental or malicious tampering.
 */
class HospitalSetting extends BaseModel
{
    protected string $table = 'hospital_settings';

    private static ?array $settingsCache = null;

    /**
     * Critical safeguards that CANNOT be disabled under any circumstances
     */
    private const CRITICAL_SAFEGUARDS = [
        'audit_logging_enabled',
        'rbac_enabled',
        'backup_safeguards_active',
        'force_https_enabled',
    ];

    /**
     * Default fallback configuration if database is unseeded or offline
     */
    public const DEFAULTS = [
        // General & Timezone
        'hospital_name'                      => 'Sheth L.G. General Hospital, Ahmedabad',
        'hospital_subtitle'                  => 'Multi-Floor Facility Inspection & Complaint Command Center',
        'hospital_address'                   => 'AMC Medical Education Trust, Maninagar, Ahmedabad - 380008, Gujarat, India',
        'app_timezone'                       => 'Asia/Kolkata',

        // Attendance & Geofencing
        'default_geofence_radius_meters'     => '150',
        'max_acceptable_gps_accuracy_meters' => '100',
        'default_shift_grace_minutes'        => '15',
        'require_gps_verification'           => '1',

        // Inspections & Scheduling Defaults
        'schedule_generation_days_ahead'     => '7',
        'auto_mark_overdue_hours'            => '2',
        'default_pass_threshold_percentage'  => '80.0',
        'enforce_qr_scan_per_ward'           => '1',

        // Grievance & SLA Targets
        'default_complaint_response_hours'   => '4',
        'default_complaint_resolution_hours' => '24',
        'auto_escalate_overdue_tickets'      => '1',

        // Notification Preferences
        'enable_in_app_notifications'        => '1',
        'enable_email_alerts'                => '0',
        'alert_on_critical_failure'          => '1',
        'alert_on_sla_breach'                => '1',

        // Retention & Backups
        'audit_log_retention_days'           => '365',
        'evidence_file_retention_days'       => '180',
        'backup_retention_days'              => '30',
        'automated_backup_enabled'           => '1',

        // Maintenance Mode & Alerts
        'maintenance_mode_enabled'           => '0',
        'maintenance_notice_message'         => 'Sheth L.G. General Hospital Portal is undergoing scheduled clinical maintenance. For emergency ward coordination, contact the control room at +91-79-25461380.',
    ];

    /**
     * Get a setting value by key with memory cache and default fallback
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        if (self::$settingsCache === null) {
            self::loadCache();
        }

        if (isset(self::$settingsCache[$key])) {
            return self::$settingsCache[$key];
        }

        return $default ?? (self::DEFAULTS[$key] ?? null);
    }

    /**
     * Set a single setting value
     */
    public static function set(string $key, mixed $value, ?int $userId = null): bool
    {
        $res = self::updateBatch([$key => $value], $userId);
        return empty($res['errors']);
    }

    /**
     * Get all settings as key-value associative array
     */
    public static function getAll(): array
    {
        if (self::$settingsCache === null) {
            self::loadCache();
        }

        return array_merge(self::DEFAULTS, self::$settingsCache ?? []);
    }

    /**
     * Get all settings grouped by setting_group
     */
    public static function getAllGrouped(): array
    {
        $all = self::getAll();
        $grouped = [
            'general'               => [],
            'attendance'            => [],
            'inspections'           => [],
            'complaints'            => [],
            'notifications'         => [],
            'retention_and_backups' => [],
            'maintenance'           => [],
        ];

        try {
            $rows = Database::selectAll("SELECT setting_key, setting_value, setting_group, description, is_system FROM `hospital_settings`");
            foreach ($rows as $r) {
                $grp = $r['setting_group'] ?? 'general';
                if (!isset($grouped[$grp])) {
                    $grouped[$grp] = [];
                }
                $grouped[$grp][$r['setting_key']] = $r;
            }
        } catch (\Throwable $e) {
            // Fallback for offline / unseeded states
            foreach ($all as $k => $v) {
                $grp = self::inferGroup($k);
                $grouped[$grp][$k] = [
                    'setting_key'   => $k,
                    'setting_value' => $v,
                    'setting_group' => $grp,
                    'description'   => ucwords(str_replace('_', ' ', $k)),
                    'is_system'     => 1,
                ];
            }
        }

        return $grouped;
    }

    /**
     * Validate and update a batch of settings with safeguard defense and audit logging
     */
    public static function updateBatch(array $incomingSettings, ?int $userId = null): array
    {
        $errors = [];
        $validated = [];

        // 1. Critical Safeguard Protection: Disallow turning off essential protections
        foreach ($incomingSettings as $k => $v) {
            if (in_array($k, self::CRITICAL_SAFEGUARDS, true)) {
                $strVal = strtolower(trim((string)$v));
                if (in_array($strVal, ['0', 'false', 'off', 'disabled', 'no'], true)) {
                    $errors[] = "Critical Security Alert: Modifying or disabling safeguard '{$k}' is prohibited by hospital compliance policy.";
                }
            }
        }

        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        // 2. Comprehensive Validation Rules
        foreach ($incomingSettings as $key => $rawVal) {
            $val = trim((string)$rawVal);

            switch ($key) {
                case 'hospital_name':
                    if (strlen($val) < 3 || strlen($val) > 200) {
                        $errors[] = "Hospital name must be between 3 and 200 characters.";
                    } else {
                        $validated[$key] = $val;
                    }
                    break;

                case 'hospital_subtitle':
                case 'hospital_address':
                case 'maintenance_notice_message':
                    $validated[$key] = $val;
                    break;

                case 'app_timezone':
                    if (!in_array($val, DateTimeZone::listIdentifiers(), true)) {
                        $errors[] = "Invalid clinical timezone identifier '{$val}'. Expected standard timezone (e.g. Asia/Kolkata).";
                    } else {
                        $validated[$key] = $val;
                    }
                    break;

                case 'default_geofence_radius_meters':
                    $meters = filter_var($val, FILTER_VALIDATE_INT);
                    if ($meters === false || $meters < 10 || $meters > 5000) {
                        $errors[] = "Default geofence radius must be an integer between 10m and 5,000m.";
                    } else {
                        $validated[$key] = (string)$meters;
                    }
                    break;

                case 'max_acceptable_gps_accuracy_meters':
                    $acc = filter_var($val, FILTER_VALIDATE_INT);
                    if ($acc === false || $acc < 10 || $acc > 1000) {
                        $errors[] = "Maximum acceptable GPS accuracy must be an integer between 10m and 1,000m.";
                    } else {
                        $validated[$key] = (string)$acc;
                    }
                    break;

                case 'default_shift_grace_minutes':
                    $mins = filter_var($val, FILTER_VALIDATE_INT);
                    if ($mins === false || $mins < 0 || $mins > 120) {
                        $errors[] = "Shift grace period must be an integer between 0 and 120 minutes.";
                    } else {
                        $validated[$key] = (string)$mins;
                    }
                    break;

                case 'schedule_generation_days_ahead':
                    $days = filter_var($val, FILTER_VALIDATE_INT);
                    if ($days === false || $days < 1 || $days > 60) {
                        $errors[] = "Schedule generation horizon must be an integer between 1 and 60 days.";
                    } else {
                        $validated[$key] = (string)$days;
                    }
                    break;

                case 'auto_mark_overdue_hours':
                case 'default_complaint_response_hours':
                case 'default_complaint_resolution_hours':
                    $hrs = filter_var($val, FILTER_VALIDATE_INT);
                    if ($hrs === false || $hrs < 1 || $hrs > 720) {
                        $errors[] = "Hours value for '{$key}' must be between 1 and 720 hours.";
                    } else {
                        $validated[$key] = (string)$hrs;
                    }
                    break;

                case 'default_pass_threshold_percentage':
                    $pct = filter_var($val, FILTER_VALIDATE_FLOAT);
                    if ($pct === false || $pct < 1.0 || $pct > 100.0) {
                        $errors[] = "Checklist pass threshold must be a percentage between 1.0% and 100.0%.";
                    } else {
                        $validated[$key] = number_format($pct, 1, '.', '');
                    }
                    break;

                case 'audit_log_retention_days':
                    $retDays = filter_var($val, FILTER_VALIDATE_INT);
                    if ($retDays === false || $retDays < 30 || $retDays > 3650) {
                        $errors[] = "Audit log retention period must be at least 30 days and at most 3,650 days (10 years) for compliance.";
                    } else {
                        $validated[$key] = (string)$retDays;
                    }
                    break;

                case 'evidence_file_retention_days':
                case 'backup_retention_days':
                    $bDays = filter_var($val, FILTER_VALIDATE_INT);
                    if ($bDays === false || $bDays < 7 || $bDays > 3650) {
                        $errors[] = "Retention days for '{$key}' must be at least 7 days.";
                    } else {
                        $validated[$key] = (string)$bDays;
                    }
                    break;

                case 'maintenance_mode_enabled':
                case 'require_gps_verification':
                case 'enforce_qr_scan_per_ward':
                case 'auto_escalate_overdue_tickets':
                case 'enable_in_app_notifications':
                case 'enable_email_alerts':
                case 'alert_on_critical_failure':
                case 'alert_on_sla_breach':
                case 'automated_backup_enabled':
                    $validated[$key] = in_array($val, ['1', 'true', 'on', 'yes'], true) ? '1' : '0';
                    break;

                default:
                    // Allow other alphanumeric custom setting keys
                    if (preg_match('/^[a-zA-Z0-9_]{2,80}$/', $key)) {
                        $validated[$key] = $val;
                    }
                    break;
            }
        }

        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        // 3. Persist Validated Settings & Record Audit Trail
        $oldValues = self::getAll();
        $updatedKeys = [];

        try {
            foreach ($validated as $k => $v) {
                $group = self::inferGroup($k);
                $sql = "INSERT INTO `hospital_settings` (`setting_key`, `setting_value`, `setting_group`, `description`, `updated_by`, `updated_at`)
                        VALUES (:key, :val, :grp, :desc, :uid, NOW())
                        ON DUPLICATE KEY UPDATE 
                            `setting_value` = VALUES(`setting_value`),
                            `updated_by` = VALUES(`updated_by`),
                            `updated_at` = NOW()";

                Database::executeStatement($sql, [
                    'key'  => $k,
                    'val'  => $v,
                    'grp'  => $group,
                    'desc' => ucwords(str_replace('_', ' ', $k)),
                    'uid'  => $userId,
                ]);

                $updatedKeys[$k] = $v;
                if (self::$settingsCache !== null) {
                    self::$settingsCache[$k] = $v;
                }
            }

            // Log forensic audit trail entry
            AuditLog::record(
                $userId,
                'HOSPITAL_SETTINGS_UPDATED',
                'hospital_settings',
                null,
                array_intersect_key($oldValues, $updatedKeys),
                $updatedKeys
            );

            return ['success' => true, 'updated' => $updatedKeys];
        } catch (\Throwable $e) {
            // Memory-level update if DB is disconnected
            foreach ($validated as $k => $v) {
                self::$settingsCache[$k] = $v;
            }
            return ['success' => true, 'updated' => $validated, 'warning' => 'Updated in memory context'];
        }
    }

    /**
     * Infer setting group from setting key
     */
    private static function inferGroup(string $key): string
    {
        if (str_starts_with($key, 'hospital_') || str_starts_with($key, 'app_')) {
            return 'general';
        }
        if (str_contains($key, 'gps') || str_contains($key, 'geofence') || str_contains($key, 'grace')) {
            return 'attendance';
        }
        if (str_contains($key, 'schedule') || str_contains($key, 'checklist') || str_contains($key, 'pass_threshold') || str_contains($key, 'qr_scan')) {
            return 'inspections';
        }
        if (str_contains($key, 'complaint') || str_contains($key, 'sla') || str_contains($key, 'escalat')) {
            return 'complaints';
        }
        if (str_contains($key, 'notification') || str_contains($key, 'alert')) {
            return 'notifications';
        }
        if (str_contains($key, 'retention') || str_contains($key, 'backup')) {
            return 'retention_and_backups';
        }
        if (str_contains($key, 'maintenance')) {
            return 'maintenance';
        }
        return 'general';
    }

    /**
     * Load settings from database into memory cache
     */
    private static function loadCache(): void
    {
        self::$settingsCache = [];
        try {
            $rows = Database::selectAll("SELECT setting_key, setting_value FROM `hospital_settings`");
            foreach ($rows as $r) {
                self::$settingsCache[$r['setting_key']] = $r['setting_value'];
            }
        } catch (\Throwable $e) {
            // DB not yet migrated or offline: use defaults
            self::$settingsCache = self::DEFAULTS;
        }
    }

    /**
     * Reset cache (for test suites)
     */
    public static function resetCache(): void
    {
        self::$settingsCache = null;
    }
}
