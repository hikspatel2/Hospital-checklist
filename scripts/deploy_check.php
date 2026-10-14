<?php
declare(strict_types=1);

/**
 * ==============================================================================
 * Sheth L.G. General Hospital - Deployment Pre-Flight Verification
 * ==============================================================================
 * Usage: php scripts/deploy_check.php
 * 
 * Verifies Hostinger shared hosting environment readiness, PHP extensions,
 * writable directory permissions, security configurations, and database connectivity.
 */

$basePath = dirname(__DIR__);
require_once $basePath . '/app/Core/App.php';
require_once $basePath . '/app/Core/Helpers.php';

use App\Core\App;
use App\Core\Database;

$app = new App($basePath);

echo "\n====================================================================\n";
echo " SHETH L.G. GENERAL HOSPITAL - HOSTINGER PRE-FLIGHT CHECK (PHASE 12)\n";
echo "====================================================================\n\n";

$passes = 0;
$warnings = 0;
$failures = 0;

function check(string $label, bool $status, string $message = '', bool $isWarning = false): void {
    global $passes, $warnings, $failures;
    if ($status) {
        $passes++;
        echo " [PASS] {$label}\n";
    } elseif ($isWarning) {
        $warnings++;
        echo " [WARN] {$label} - {$message}\n";
    } else {
        $failures++;
        echo " [FAIL] {$label} - {$message}\n";
    }
}

// 1. PHP Version
$phpVersion = PHP_VERSION;
check("PHP Version >= 8.1 (Current: {$phpVersion})", version_compare(PHP_VERSION, '8.1.0', '>='), 'PHP 8.1 or higher is required.');

// 2. PHP Extensions
$mandatoryExtensions = [
    'pdo' => 'Database connectivity core',
    'pdo_mysql' => 'MySQL/MariaDB driver',
    'mbstring' => 'Multibyte string operations',
    'openssl' => 'Encryption and token generation',
    'json' => 'JSON payload handling and REST responses',
    'ctype' => 'Character type validation',
    'fileinfo' => 'MIME type sniffing for secure uploads',
    'session' => 'Session state management',
];

$optionalExtensions = [
    'gd' => 'Optional image manipulation & thumbnail rendering',
    'curl' => 'Optional external webhook/HTTP integrations',
];

foreach ($mandatoryExtensions as $ext => $purpose) {
    check("Core Extension: {$ext} ({$purpose})", extension_loaded($ext), "Core extension '{$ext}' is missing.");
}

foreach ($optionalExtensions as $ext => $purpose) {
    check("Recommended Extension: {$ext} ({$purpose})", extension_loaded($ext), "Extension '{$ext}' is not loaded (optional).", true);
}

// 3. Recommended PHP INI Settings
$uploadMax = ini_get('upload_max_filesize');
$postMax = ini_get('post_max_size');
$memoryLimit = ini_get('memory_limit');
$maxExecution = (int)ini_get('max_execution_time');

check("PHP upload_max_filesize ({$uploadMax})", (int)$uploadMax >= 10, "Current is {$uploadMax}; recommended >= 10M", true);
check("PHP post_max_size ({$postMax})", (int)$postMax >= 10, "Current is {$postMax}; recommended >= 10M", true);
check("PHP memory_limit ({$memoryLimit})", (int)$memoryLimit >= 128 || (int)$memoryLimit === -1, "Current is {$memoryLimit}; recommended >= 128M", true);
check("PHP max_execution_time ({$maxExecution}s)", $maxExecution >= 30 || $maxExecution === 0, "Current is {$maxExecution}s; recommended >= 60s for backups", true);

// 4. Directory Permissions (Writable checks)
$writableDirectories = [
    'storage' => $basePath . '/storage',
    'storage/logs' => $basePath . '/storage/logs',
    'storage/backups' => $basePath . '/storage/backups',
    'storage/uploads' => $basePath . '/storage/uploads',
    'storage/uploads/complaints' => $basePath . '/storage/uploads/complaints',
    'storage/temp' => $basePath . '/storage/temp',
];

foreach ($writableDirectories as $dirName => $dirPath) {
    if (!is_dir($dirPath)) {
        @mkdir($dirPath, 0750, true);
    }
    $isWritable = is_dir($dirPath) && is_writable($dirPath);
    check("Writable Directory: {$dirName}", $isWritable, "Directory '{$dirPath}' must exist and have write permissions (0750/0755).");
}

// 5. Security & Configuration Checks
$envFileExists = file_exists($basePath . '/.env');
check("Environment File (.env exists)", $envFileExists, "Missing .env file in application root.");

if ($envFileExists) {
    $appKey = (string)config('app.key', '');
    $isKeyValid = strlen($appKey) >= 32;
    check("Application Key (APP_KEY >= 32 chars)", $isKeyValid, "APP_KEY must be a cryptographically secure random string with at least 32 characters.");

    $appEnv = (string)config('app.env', 'development');
    $appDebug = (bool)config('app.debug', false);

    if ($appEnv === 'production') {
        check("Production Debug Mode Disabled (APP_DEBUG=false)", !$appDebug, "APP_DEBUG is true in production! Must be set to false.", false);
        check("Session Secure Cookie (SESSION_SECURE_COOKIE)", (bool)config('app.session.secure', false), "SESSION_SECURE_COOKIE should be true on production HTTPS host.", true);
    } else {
        check("Current Environment: {$appEnv} (Debug: " . ($appDebug ? 'true' : 'false') . ")", true);
    }
}

// 6. Security Protection Files (.htaccess)
check("Public .htaccess exists", file_exists($basePath . '/public/.htaccess'), "public/.htaccess missing.");
check("Storage .htaccess exists (Deny all)", file_exists($basePath . '/storage/.htaccess'), "storage/.htaccess missing.");
check("Root .htaccess exists (Hostinger fallback)", file_exists($basePath . '/.htaccess'), "Root .htaccess missing.");

// 7. Database Connectivity & Migration Schema Check
echo "\n--- Database Verification ---\n";
try {
    $pdo = Database::getConnection();
    check("Database Connection (PDO MySQL)", true);

    // Verify charset
    $stmt = $pdo->query("SELECT @@character_set_connection AS cs, @@collation_connection AS cl");
    $row = $stmt->fetch();
    check("Database Character Set ({$row['cs']})", str_contains((string)$row['cs'], 'utf8mb4'), "Recommended character set is utf8mb4");

    // Check core tables
    $expectedTables = [
        'schema_migrations',
        'users',
        'buildings',
        'floors',
        'wards',
        'shifts',
        'checklist_templates',
        'checklist_items',
        'inspection_schedules',
        'inspections',
        'inspection_results',
        'complaints',
        'complaint_history',
        'attendance_logs',
        'audit_logs',
        'backups',
        'hospital_settings',
    ];

    $tablesStmt = $pdo->query("SHOW TABLES");
    $existingTables = $tablesStmt->fetchAll(\PDO::FETCH_COLUMN);

    $missingTables = array_diff($expectedTables, $existingTables);
    if (empty($missingTables)) {
        check("Database Tables Present (" . count($expectedTables) . "/" . count($expectedTables) . " verified)", true);
    } else {
        check("Database Tables Present", false, "Missing tables: " . implode(', ', $missingTables) . ". Run 'php scripts/migrate.php'.");
    }

    // Check applied migrations count
    if (in_array('schema_migrations', $existingTables, true)) {
        $migCount = (int)$pdo->query("SELECT COUNT(*) FROM `schema_migrations`")->fetchColumn();
        check("Database Migrations Applied ({$migCount} versions recorded)", $migCount > 0, "No migrations have been recorded in schema_migrations.", true);
    }

    // Check admin account presence
    $adminStmt = $pdo->query("SELECT COUNT(*) FROM `users` WHERE `role` = 'admin' AND `status` = 'active'");
    $adminCount = (int)$adminStmt->fetchColumn();
    if ($adminCount > 0) {
        check("Active Administrator Account Present ({$adminCount} admin(s) found)", true);
    } else {
        check("Active Administrator Account Present", false, "No active administrator account found. Run 'php scripts/create_admin.php'.", true);
    }

} catch (\Throwable $e) {
    check("Database Connection", false, "Could not connect to database: " . $e->getMessage());
}

// Summary
echo "\n====================================================================\n";
echo " PRE-FLIGHT VERIFICATION SUMMARY:\n";
echo " Passes:   {$passes}\n";
echo " Warnings: {$warnings}\n";
echo " Failures: {$failures}\n";
echo "====================================================================\n\n";

if ($failures > 0) {
    echo "[STATUS] NOT READY FOR PRODUCTION: {$failures} critical issue(s) must be resolved.\n";
    exit(1);
} elseif ($warnings > 0) {
    echo "[STATUS] READY WITH WARNINGS: Review warning messages before production launch.\n";
    exit(0);
} else {
    echo "[STATUS] ENVIRONMENT 100% READY FOR HOSTINGER PRODUCTION DEPLOYMENT!\n";
    exit(0);
}
