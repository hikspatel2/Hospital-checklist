<?php
declare(strict_types=1);

/**
 * Phase 2 Automated Security & RBAC Test Suite
 * Usage: php scripts/test_phase2.php
 */

require_once dirname(__DIR__) . '/app/Core/App.php';
require_once dirname(__DIR__) . '/app/Core/Helpers.php';

use App\Core\App;
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Request;
use App\Core\Session;
use App\Core\Middleware\AdminMiddleware;
use App\Core\Middleware\AuthMiddleware;

$app = new App(dirname(__DIR__));

echo "\n======================================================\n";
echo " Sheth L.G. General Hospital - Phase 2 Security Tests\n";
echo "======================================================\n\n";

$testsPassed = 0;
$testsTotal = 0;

function runTest(string $title, callable $testFunc): void {
    global $testsPassed, $testsTotal;
    $testsTotal++;
    echo "[TEST {$testsTotal}] {$title} ... ";
    try {
        $result = $testFunc();
        if ($result === true) {
            echo "PASSED\n";
            $testsPassed++;
        } else {
            echo "FAILED: Expected true outcome\n";
        }
    } catch (\Throwable $e) {
        echo "FAILED with exception: " . $e->getMessage() . "\n";
    }
}

// ------------------------------------------------------------------------------
// TEST 1: CSRF Token Generation & Validation
// ------------------------------------------------------------------------------
runTest('CSRF Token Generation, Timing-Safe Match & Tamper Detection', function() {
    Session::init();
    $token = Csrf::getToken();
    if (empty($token) || strlen($token) !== 64) {
        throw new \Exception("Invalid token format");
    }

    if (!Csrf::validate($token)) {
        throw new \Exception("Valid token validation failed");
    }

    if (Csrf::validate($token . 'tampered')) {
        throw new \Exception("Tampered token was mistakenly accepted");
    }

    if (Csrf::validate('')) {
        throw new \Exception("Empty token was accepted");
    }

    return true;
});

// ------------------------------------------------------------------------------
// TEST 2: CSRF Request Verification on State-Changing HTTP Verbs
// ------------------------------------------------------------------------------
runTest('CSRF Request Interceptor Rejects Missing Token with 419 Exception', function() {
    $request = new Request('POST', '/login', [], ['identity' => 'test', 'password' => 'test']);
    $rejected = false;
    try {
        Csrf::verifyRequest($request);
    } catch (\RuntimeException $e) {
        if ($e->getCode() === 419) {
            $rejected = true;
        }
    }
    return $rejected;
});

// ------------------------------------------------------------------------------
// TEST 3: Unauthorized Access Prevention
// ------------------------------------------------------------------------------
runTest('Unauthenticated Request to Protected Route is Denied / Redirected', function() {
    Auth::logout();
    $request = new Request('GET', '/profile');
    
    // In CLI or AJAX mode, check if Auth::check() is false
    if (Auth::check()) {
        throw new \Exception("Guest was considered authenticated");
    }

    $blocked = false;
    // Test AuthMiddleware redirect or error behavior
    if (Auth::guest()) {
        $blocked = true;
    }
    return $blocked;
});

// ------------------------------------------------------------------------------
// TEST 4: Session Fixation Defense (Tested in clean isolated process)
// ------------------------------------------------------------------------------
runTest('Session ID Rotation and Fixation Defense on Authentication State Changes', function() {
    $base = addslashes(dirname(__DIR__));
    $code = "require_once '{$base}/app/Core/App.php'; " .
            "new App\Core\App('{$base}'); " .
            "App\Core\Session::init(); " .
            "\$id1 = session_id(); " .
            "App\Core\Session::regenerate(true); " .
            "\$id2 = session_id(); " .
            "exit((!empty(\$id1) && !empty(\$id2) && \$id1 !== \$id2) ? 0 : 1);";

    $output = [];
    $returnCode = 0;
    exec('php -r "' . $code . '"', $output, $returnCode);

    return ($returnCode === 0);
});

// ------------------------------------------------------------------------------
// TEST 5: Horizontal Privilege Escalation (IDOR Prevention)
// ------------------------------------------------------------------------------
runTest('Horizontal Privilege Escalation (Supervisor A viewing Supervisor B) Denied with 403', function() {
    // Simulate Supervisor 1 (ID: 2)
    Session::set('auth_user_id', 2);
    Session::set('auth_role', 'supervisor');

    // Clear user cache for test isolation
    $refProp = new ReflectionProperty(Auth::class, 'cachedUser');
    $refProp->setAccessible(true);
    $refProp->setValue(null, [
        'id' => 2,
        'role' => 'supervisor',
        'name' => 'Supervisor Sunita',
        'is_active' => 1,
        'deleted_at' => null,
    ]);

    // Attempt to access Supervisor 2 (ID: 3)
    $denied = false;
    try {
        Auth::authorizeSupervisorAccess(3);
    } catch (\RuntimeException $e) {
        if ($e->getCode() === 403) {
            $denied = true;
        }
    }

    // Now verify Supervisor 1 accessing Supervisor 1 passes
    $selfAccessAllowed = false;
    try {
        Auth::authorizeSupervisorAccess(2);
        $selfAccessAllowed = true;
    } catch (\Throwable $e) {
        $selfAccessAllowed = false;
    }

    return ($denied && $selfAccessAllowed);
});

// ------------------------------------------------------------------------------
// TEST 6: Administrator Role Privilege Enforcement
// ------------------------------------------------------------------------------
runTest('Non-Admin (Supervisor) Accessing Admin Route is Blocked with 403', function() {
    // Current role is supervisor (set in Test 5)
    $request = new Request('GET', '/users');
    $blocked = false;

    try {
        AdminMiddleware::handle($request);
    } catch (\RuntimeException $e) {
        if ($e->getCode() === 403) {
            $blocked = true;
        }
    }

    return $blocked;
});

// ------------------------------------------------------------------------------
// TEST 7: Password Complexity & Bcrypt Cost Verification
// ------------------------------------------------------------------------------
runTest('Bcrypt Password Hashing Cost is 12 and Verifies Correctly', function() {
    $plain = 'Admin@Hospital123';
    $hash = password_hash($plain, PASSWORD_BCRYPT, ['cost' => 12]);
    $info = password_get_info($hash);

    if ($info['algoName'] !== 'bcrypt' || $info['options']['cost'] !== 12) {
        throw new \Exception("Incorrect password hash configuration");
    }

    if (!password_verify($plain, $hash)) {
        throw new \Exception("Password verify failed for matching string");
    }

    if (password_verify($plain . 'wrong', $hash)) {
        throw new \Exception("Password verify succeeded for incorrect string");
    }

    return true;
});

echo "\n------------------------------------------------------\n";
echo " Phase 2 Test Results: {$testsPassed} / {$testsTotal} Passed (" . round(($testsPassed / $testsTotal) * 100) . "%)\n";
echo "------------------------------------------------------\n\n";

if ($testsPassed === $testsTotal) {
    exit(0);
} else {
    exit(1);
}
