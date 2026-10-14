<?php
declare(strict_types=1);

namespace App\Core;

use App\Models\AuditLog;
use App\Models\User;

/**
 * Authentication & Authorization Service
 * Implements login rate-limiting, lockout defense, session fixation prevention,
 * role-based access control, and horizontal privilege escalation prevention.
 */
class Auth
{
    private const SESSION_USER_KEY = 'auth_user_id';
    private const MAX_FAILED_ATTEMPTS = 5;
    private const LOCKOUT_MINUTES = 15;

    private static ?array $cachedUser = null;

    /**
     * Attempt login with employee ID or email
     */
    public static function attempt(string $identity, string $password, string $ip = '', string $userAgent = ''): array
    {
        $identity = trim($identity);
        if ($identity === '' || $password === '') {
            return [
                'success' => false,
                'error' => 'Please enter both your Employee ID / Email and password.',
            ];
        }

        $userModel = new User();
        $user = $userModel->findByIdentity($identity);

        // Account Lockout Verification
        if ($user !== null && !empty($user['locked_until'])) {
            $lockedUntil = strtotime($user['locked_until']);
            if ($lockedUntil > time()) {
                $remainingMinutes = (int)ceil(($lockedUntil - time()) / 60);

                AuditLog::record(
                    $user['id'],
                    'LOGIN_LOCKED_ATTEMPT',
                    'users',
                    (int)$user['id'],
                    null,
                    ['remaining_minutes' => $remainingMinutes],
                    $ip,
                    $userAgent
                );

                return [
                    'success' => false,
                    'error' => "Account is temporarily locked due to multiple failed login attempts. Please try again after {$remainingMinutes} minute(s).",
                ];
            } else {
                // Lockout period has elapsed; reset lock
                $userModel->resetLockout((int)$user['id']);
                $user['failed_login_attempts'] = 0;
                $user['locked_until'] = null;
            }
        }

        // Validate credentials & status
        $isValidPassword = ($user !== null) && password_verify($password, $user['password_hash']);
        $isActive = ($user !== null) && (int)$user['is_active'] === 1 && empty($user['deleted_at']);

        if (!$isValidPassword || !$isActive) {
            if ($user !== null) {
                // Increment failed attempts and trigger lockout if threshold reached
                $failedAttempts = ((int)$user['failed_login_attempts']) + 1;
                $lockUntil = null;

                if ($failedAttempts >= self::MAX_FAILED_ATTEMPTS) {
                    $lockUntil = date('Y-m-d H:i:s', time() + (self::LOCKOUT_MINUTES * 60));
                }

                $userModel->recordFailedLogin((int)$user['id'], $failedAttempts, $lockUntil);

                AuditLog::record(
                    $user['id'],
                    'LOGIN_FAILED',
                    'users',
                    (int)$user['id'],
                    null,
                    ['attempt_count' => $failedAttempts, 'locked' => ($lockUntil !== null)],
                    $ip,
                    $userAgent
                );

                if ($lockUntil !== null) {
                    return [
                        'success' => false,
                        'error' => "Account locked due to 5 consecutive failed login attempts. Try again in " . self::LOCKOUT_MINUTES . " minutes.",
                    ];
                }
            } else {
                // Unknown account attempt
                AuditLog::record(
                    null,
                    'LOGIN_FAILED_UNKNOWN_USER',
                    'users',
                    null,
                    null,
                    ['identity_snippet' => substr($identity, 0, 3) . '***'],
                    $ip,
                    $userAgent
                );
            }

            // Always return generic error message to prevent account enumeration
            return [
                'success' => false,
                'error' => 'Invalid credentials or inactive facility account.',
            ];
        }

        // Login Success: Reset counters & record session
        $userModel->recordSuccessfulLogin((int)$user['id'], $ip);

        // Session fixation protection: regenerate session ID
        Session::regenerate(true);
        Session::set(self::SESSION_USER_KEY, (int)$user['id']);
        Session::set('auth_role', $user['role']);
        Session::set('auth_logged_in_at', now_kolkata());

        self::$cachedUser = $user;

        AuditLog::record(
            (int)$user['id'],
            'LOGIN_SUCCESS',
            'users',
            (int)$user['id'],
            null,
            ['role' => $user['role']],
            $ip,
            $userAgent
        );

        return [
            'success' => true,
            'user' => $user,
        ];
    }

    /**
     * Check if a user is authenticated in the current session
     */
    public static function check(): bool
    {
        return self::user() !== null;
    }

    /**
     * Check if current visitor is a guest
     */
    public static function guest(): bool
    {
        return !self::check();
    }

    /**
     * Retrieve authenticated user record (with active status re-validation)
     */
    public static function user(): ?array
    {
        if (self::$cachedUser !== null) {
            return self::$cachedUser;
        }

        $userId = Session::get(self::SESSION_USER_KEY);
        if (!$userId || !is_numeric($userId)) {
            return null;
        }

        $userModel = new User();
        $user = $userModel->findActiveById((int)$userId);

        if ($user === null) {
            // User deactivated or deleted while session was open; destroy session
            self::logout();
            return null;
        }

        self::$cachedUser = $user;
        return $user;
    }

    /**
     * Get current user ID
     */
    public static function id(): ?int
    {
        $user = self::user();
        return $user ? (int)$user['id'] : null;
    }

    /**
     * Get current user role
     */
    public static function role(): ?string
    {
        $user = self::user();
        return $user ? (string)$user['role'] : null;
    }

    /**
     * Check if authenticated user is Administrator
     */
    public static function isAdmin(): bool
    {
        return self::role() === 'admin';
    }

    /**
     * Check if authenticated user is Supervisor
     */
    public static function isSupervisor(): bool
    {
        return self::role() === 'supervisor';
    }

    /**
     * Terminate authenticated session
     */
    public static function logout(): void
    {
        $userId = Session::get(self::SESSION_USER_KEY);
        if ($userId) {
            AuditLog::record(
                (int)$userId,
                'LOGOUT',
                'users',
                (int)$userId,
                null,
                ['session_terminated' => true],
                $_SERVER['REMOTE_ADDR'] ?? '',
                $_SERVER['HTTP_USER_AGENT'] ?? ''
            );
        }

        self::$cachedUser = null;
        Session::remove(self::SESSION_USER_KEY);
        Session::remove('auth_role');
        Session::remove('auth_logged_in_at');
        Session::destroy();
    }

    /**
     * Enforce horizontal privilege authorization for supervisor resources
     * Prevents a supervisor from inspecting another supervisor's records by tampering with IDs in URLs.
     */
    public static function authorizeSupervisorAccess(int $targetSupervisorId): void
    {
        if (self::isAdmin()) {
            return; // Admins have facility-wide oversight
        }

        $currentUserId = self::id();
        if ($currentUserId === null || $currentUserId !== $targetSupervisorId) {
            AuditLog::record(
                $currentUserId,
                'UNAUTHORIZED_HORIZONTAL_ACCESS_ATTEMPT',
                'users',
                $targetSupervisorId,
                null,
                ['attempted_target_id' => $targetSupervisorId],
                $_SERVER['REMOTE_ADDR'] ?? '',
                $_SERVER['HTTP_USER_AGENT'] ?? ''
            );

            throw new \RuntimeException('Authorization Denied: You do not have permission to access another supervisor\'s records.', 403);
        }
    }
}
