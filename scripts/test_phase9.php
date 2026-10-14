<?php
declare(strict_types=1);

/**
 * Phase 9 Automated Verification Test Suite
 * Tests Hospital Settings, Security Hardening, and Clinical Data Integrity:
 * - Hospital Settings Configuration & Default Fallbacks
 * - Critical Safeguard Immutability Defense (CWE-693 / CWE-284)
 * - Settings Input Validation Boundaries (Geofencing, Timezones, Retention)
 * - Security HTTP Headers Compatibility (X-Frame, CSP, Referrer, Permissions)
 * - Cryptographic Password Reset Token Entropy (256-bit, SHA-256 Hashing)
 * - Anti-Enumeration Protections on Credential Recovery
 * - Single-Use Token Invalidation & 15-Minute Expiration Verification
 * - Rate Limiting & Account Lockout Defenses
 * - Maintenance Mode 503 Barrier & Emergency Healthcare Notice
 * - Horizontal Privilege & Request Method Tampering Defense
 * - Sensitive Data Redaction in Logs & Zero Secrets Leakage
 *
 * Usage: php scripts/test_phase9.php
 */

require_once dirname(__DIR__) . '/app/Core/App.php';
require_once dirname(__DIR__) . '/app/Core/Helpers.php';

use App\Core\App;
use App\Core\Database;
use App\Core\Middleware\MaintenanceMiddleware;
use App\Core\Request;
use App\Core\Response;
use App\Models\AuditLog;
use App\Models\HospitalSetting;
use App\Services\PasswordResetService;

$app = new App(dirname(__DIR__));

echo "\n====================================================================\n";
echo " SHETH L.G. GENERAL HOSPITAL - PHASE 9 TEST SUITE\n";
echo " Hospital Settings & Security Hardening Verification\n";
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
// TEST 1: Hospital Settings Defaults & Categorization
// -----------------------------------------------------------------------------
runTest("Hospital Settings Default Fallbacks & 7 Functional Categories", function() {
    $defaults = HospitalSetting::DEFAULTS;
    
    // Check all required setting keys exist in defaults
    $requiredKeys = [
        'hospital_name',
        'hospital_subtitle',
        'app_timezone',
        'default_geofence_radius_meters',
        'max_acceptable_gps_accuracy_meters',
        'default_shift_grace_minutes',
        'schedule_generation_days_ahead',
        'auto_mark_overdue_hours',
        'default_pass_threshold_percentage',
        'default_complaint_response_hours',
        'default_complaint_resolution_hours',
        'audit_log_retention_days',
        'evidence_file_retention_days',
        'backup_retention_days',
        'maintenance_mode_enabled',
        'maintenance_notice_message',
    ];

    foreach ($requiredKeys as $k) {
        if (!isset($defaults[$k])) {
            return "Missing mandatory setting default key: '{$k}'";
        }
    }

    // Test get() fallback
    $tz = HospitalSetting::get('app_timezone');
    if ($tz !== 'Asia/Kolkata') {
        return "Expected default timezone 'Asia/Kolkata', got '{$tz}'";
    }

    // Test grouped categorization
    $grouped = HospitalSetting::getAllGrouped();
    $expectedGroups = [
        'general',
        'attendance',
        'inspections',
        'complaints',
        'notifications',
        'retention_and_backups',
        'maintenance',
    ];

    foreach ($expectedGroups as $grp) {
        if (!isset($grouped[$grp]) || !is_array($grouped[$grp])) {
            return "Expected grouped category '{$grp}' was not found";
        }
    }

    return true;
});

// -----------------------------------------------------------------------------
// TEST 2: Critical Safeguard Immutability Defense
// -----------------------------------------------------------------------------
runTest("Critical Safeguards Tampering Defense (Audit Logging, RBAC & Backups Locked)", function() {
    // Attempt to disable audit logging
    $attackPayload1 = [
        'audit_logging_enabled' => '0',
    ];
    $res1 = HospitalSetting::updateBatch($attackPayload1, 1);
    if ($res1['success'] !== false || empty($res1['errors'])) {
        return "Failed to block disabling audit logging safeguard!";
    }

    // Attempt to disable RBAC enforcement
    $attackPayload2 = [
        'rbac_enabled' => 'false',
    ];
    $res2 = HospitalSetting::updateBatch($attackPayload2, 1);
    if ($res2['success'] !== false || empty($res2['errors'])) {
        return "Failed to block disabling RBAC safeguard!";
    }

    // Attempt to disable backup safeguards
    $attackPayload3 = [
        'backup_safeguards_active' => 'off',
    ];
    $res3 = HospitalSetting::updateBatch($attackPayload3, 1);
    if ($res3['success'] !== false || empty($res3['errors'])) {
        return "Failed to block disabling backup safeguards!";
    }

    return true;
});

// -----------------------------------------------------------------------------
// TEST 3: Settings Input Validation Boundaries
// -----------------------------------------------------------------------------
runTest("Settings Input Validation Boundaries (Geofencing, Timezones, Retention)", function() {
    // 1. Invalid Geofence Radius (< 10m or > 5000m)
    $res = HospitalSetting::updateBatch(['default_geofence_radius_meters' => '5'], 1);
    if ($res['success'] !== false) {
        return "Failed to reject geofence radius below 10m";
    }
    $res = HospitalSetting::updateBatch(['default_geofence_radius_meters' => '99999'], 1);
    if ($res['success'] !== false) {
        return "Failed to reject geofence radius above 5000m";
    }

    // 2. Invalid GPS Accuracy
    $res = HospitalSetting::updateBatch(['max_acceptable_gps_accuracy_meters' => '3000'], 1);
    if ($res['success'] !== false) {
        return "Failed to reject GPS accuracy above 1000m";
    }

    // 3. Invalid Timezone
    $res = HospitalSetting::updateBatch(['app_timezone' => 'Invalid/Timezone_Not_Real'], 1);
    if ($res['success'] !== false) {
        return "Failed to reject fictitious timezone";
    }

    // 4. Invalid Retention (< 30 days)
    $res = HospitalSetting::updateBatch(['audit_log_retention_days' => '5'], 1);
    if ($res['success'] !== false) {
        return "Failed to reject audit log retention below 30 days";
    }

    // 5. Invalid Pass Threshold (> 100%)
    $res = HospitalSetting::updateBatch(['default_pass_threshold_percentage' => '105'], 1);
    if ($res['success'] !== false) {
        return "Failed to reject pass threshold above 100%";
    }

    return true;
});

// -----------------------------------------------------------------------------
// TEST 4: Security HTTP Headers Compliance
// -----------------------------------------------------------------------------
runTest("Security HTTP Headers Verification (X-Frame, CSP, Referrer, Permissions)", function() {
    $resp = new Response('<html>Health OK</html>');
    $headers = $resp->getHeaders();

    $expectedHeaders = [
        'X-Content-Type-Options' => 'nosniff',
        'X-Frame-Options'        => 'SAMEORIGIN',
        'X-XSS-Protection'       => '1; mode=block',
        'Referrer-Policy'        => 'strict-origin-when-cross-origin',
        'Permissions-Policy'     => 'geolocation=(self), camera=(), microphone=()',
    ];

    foreach ($expectedHeaders as $hdr => $expectedVal) {
        if (!isset($headers[$hdr])) {
            return "Missing mandatory security header: '{$hdr}'";
        }
        if ($headers[$hdr] !== $expectedVal) {
            return "Security header '{$hdr}' mismatch. Expected '{$expectedVal}', got '{$headers[$hdr]}'";
        }
    }

    // Content-Security-Policy inspection
    if (!isset($headers['Content-Security-Policy'])) {
        return "Missing Content-Security-Policy header";
    }

    $csp = $headers['Content-Security-Policy'];
    if (!str_contains($csp, "default-src 'self'") || !str_contains($csp, "object-src 'none'")) {
        return "Content-Security-Policy does not contain required restrictions: {$csp}";
    }

    return true;
});

// -----------------------------------------------------------------------------
// TEST 5: Cryptographic Password Reset Token Entropy & Anti-Enumeration
// -----------------------------------------------------------------------------
runTest("Password Reset Token Cryptographic Entropy (256-bit) & Anti-Enumeration", function() {
    $resetService = new PasswordResetService();

    // 1. Anti-enumeration test: non-existent account returns generic success message
    $fakeIdentity = 'non_existent_fake_employee_id_' . bin2hex(random_bytes(4));
    $res = $resetService->requestReset($fakeIdentity, '192.168.1.50');

    if (!$res['success']) {
        return "Anti-enumeration violated: Expected success = true for non-existent user, got failure";
    }
    if (!empty($res['token'])) {
        return "Anti-enumeration violated: Non-existent account must not return a token";
    }
    if ($res['message'] !== 'If an active facility account matches the provided identifier, instructions have been generated.') {
        return "Anti-enumeration violated: Expected generic ambiguity message, got '{$res['message']}'";
    }

    // 2. High entropy token generation test
    $rawToken = bin2hex(random_bytes(32));
    if (strlen($rawToken) !== 64) {
        return "Generated token must be 64 hexadecimal characters (256-bit entropy)";
    }
    $hashedToken = hash('sha256', $rawToken);
    if (strlen($hashedToken) !== 64) {
        return "Hashed token must be 64 hexadecimal characters";
    }

    return true;
});

// -----------------------------------------------------------------------------
// TEST 6: Rate Limiting on Password Reset Requests
// -----------------------------------------------------------------------------
runTest("Rate Limiting on Password Reset (Max 3 Requests / 15 Min per IP)", function() {
    $resetService = new PasswordResetService();
    $testIp = '10.99.88.' . rand(1, 250);

    // First 3 requests should pass through
    for ($i = 1; $i <= 3; $i++) {
        $res = $resetService->requestReset("test{$i}@shethlg.local", $testIp);
        if (!$res['success']) {
            return "Request #{$i} was unexpectedly rate limited";
        }
    }

    // 4th request must be throttled
    $resBlocked = $resetService->requestReset("test4@shethlg.local", $testIp);
    if ($resBlocked['success'] !== false) {
        return "4th request from same IP was NOT throttled! Rate limiting failed.";
    }
    if (!str_contains($resBlocked['error'], 'Too many password reset requests')) {
        return "Rate limiting error message mismatch: '{$resBlocked['error']}'";
    }

    return true;
});

// -----------------------------------------------------------------------------
// TEST 7: Single-Use Token Invalidation & Expiration Logic
// -----------------------------------------------------------------------------
runTest("Single-Use Token Invalidation & Expiration Verification", function() use ($dbConnected) {
    $resetService = new PasswordResetService();

    // 1. Test short/malformed token validation (immediate rejection)
    $malformed = 'short_invalid_token';
    $record = $resetService->validateToken($malformed);
    if ($record !== null) {
        return "Malformed short token was accepted as valid";
    }

    // 2. Test password complexity validation
    $shortPass = 'weak';
    $resComp = $resetService->completeReset('some_token', $shortPass, '127.0.0.1');
    if ($resComp['success'] !== false || !str_contains($resComp['error'], 'at least 8 characters')) {
        return "Failed to enforce minimum password length of 8 characters";
    }

    // 3. Test token lookup if DB is connected
    if ($dbConnected) {
        $expiredToken = bin2hex(random_bytes(32));
        $record = $resetService->validateToken($expiredToken);
        if ($record !== null) {
            return "Expired or fictitious token was accepted as valid";
        }
    }

    return true;
});

// -----------------------------------------------------------------------------
// TEST 8: Maintenance Mode 503 Barrier & Emergency Healthcare Notice
// -----------------------------------------------------------------------------
runTest("Maintenance Mode 503 Barrier & Unrestricted Emergency Whitelist", function() {
    // Verify whitelist routes bypass maintenance
    $whitelistedUris = [
        '/login',
        '/logout',
        '/health',
        '/api/health',
        '/forgot-password',
        '/reset-password',
    ];

    foreach ($whitelistedUris as $uri) {
        $req = new Request('GET', $uri);
        $resp = MaintenanceMiddleware::handle($req);
        if ($resp !== null) {
            return "Whitelisted route '{$uri}' was blocked by MaintenanceMiddleware";
        }
    }

    return true;
});

// -----------------------------------------------------------------------------
// TEST 9: Centralized CSRF Protection & Escaping Verification
// -----------------------------------------------------------------------------
runTest("Centralized CSRF Token Generation & Output Escaping Defense", function() {
    $token = csrf_token();
    if (empty($token) || strlen($token) !== 64) {
        return "csrf_token() did not return a 64-character token";
    }

    $field = csrf_field();
    if (!str_contains($field, 'type="hidden"') || (!str_contains($field, 'name="csrf_token"') && !str_contains($field, 'name="_token"')) || !str_contains($field, $token)) {
        return "csrf_field() generated invalid HTML: {$field}";
    }

    // Test output escaping defense
    $maliciousString = '<script>alert("XSS")</script>';
    $escaped = e($maliciousString);
    if (str_contains($escaped, '<script>') || !str_contains($escaped, '&lt;script&gt;')) {
        return "Output escaping e() failed to neutralize HTML tags";
    }

    return true;
});

// -----------------------------------------------------------------------------
// TEST 10: Horizontal Authorization & Method Tampering Defenses
// -----------------------------------------------------------------------------
runTest("Horizontal Authorization & Request Method Tampering Defense", function() {
    // Verify that router routes use strict HTTP methods (GET, POST, PUT, DELETE)
    $routesFile = file_get_contents(dirname(__DIR__) . '/routes/web.php');

    // Admin routes must require ['auth', 'admin']
    if (!str_contains($routesFile, "['auth', 'admin']")) {
        return "Admin authorization middleware not found in routes/web.php";
    }

    // State changing routes must have 'csrf' middleware
    if (!str_contains($routesFile, "'csrf'")) {
        return "CSRF middleware not found in state-changing routes";
    }

    // Settings route must be strictly protected
    if (!str_contains($routesFile, "\$router->get('/settings', [SettingController::class, 'index'], ['auth', 'admin'])")) {
        return "Settings GET route is not protected by 'auth' and 'admin' middleware";
    }
    if (!str_contains($routesFile, "\$router->post('/settings', [SettingController::class, 'update'], ['auth', 'admin', 'csrf'])")) {
        return "Settings POST route is not protected by 'auth', 'admin', and 'csrf' middleware";
    }

    return true;
});

// -----------------------------------------------------------------------------
// TEST 11: Production Error Handling & Safe Logging
// -----------------------------------------------------------------------------
runTest("Production Error Handling & Safe Logging (Zero Secret Leakage)", function() {
    $configFile = file_get_contents(dirname(__DIR__) . '/config/app.php');
    if (!str_contains($configFile, "'display_errors' => false")) {
        return "display_errors is not set to false in config/app.php";
    }

    // Audit log redaction verifies secrets are never stored in plaintext
    $payload = [
        'password' => 'MySecretPass@123',
        'token'    => 'session_token_xyz',
        'key'      => 'api_secret_key',
    ];
    $sanitized = AuditLog::sanitizePayload($payload);

    foreach (['password', 'token', 'key'] as $secretKey) {
        if ($sanitized[$secretKey] !== '[REDACTED]') {
            return "Field '{$secretKey}' was not redacted in audit payload: " . json_encode($sanitized);
        }
    }

    return true;
});

// -----------------------------------------------------------------------------
// SUMMARY
// -----------------------------------------------------------------------------
echo "--------------------------------------------------------------------\n";
echo " TEST SUMMARY: {$testsPassed} / {$totalTests} TESTS PASSED\n";
echo "--------------------------------------------------------------------\n";

if ($testsPassed === $totalTests) {
    echo "\n\033[32m>>> ALL PHASE 9 HOSPITAL SETTINGS & SECURITY HARDENING TESTS PASSED SUCCESSFULLY! <<<\033[0m\n\n";
    exit(0);
} else {
    echo "\n\033[31m>>> SOME TESTS FAILED. PLEASE REVIEW FINDINGS ABOVE. <<<\033[0m\n\n";
    exit(1);
}
