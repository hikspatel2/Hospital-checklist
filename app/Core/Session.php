<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Secure Session Manager implementing cookie hardening, session fixation protection,
 * inactivity timeout, and flash data storage.
 */
class Session
{
    private static bool $started = false;

    public static function init(array $config = []): void
    {
        if (self::$started || session_status() === PHP_SESSION_ACTIVE) {
            self::$started = true;
            return;
        }

        $lifetime = ($config['lifetime'] ?? 60) * 60;
        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
                   (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) ||
                   (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

        $secure = $config['secure'] ?? $isHttps;
        $httponly = $config['httponly'] ?? true;
        $samesite = $config['samesite'] ?? 'Lax';
        $sessionName = $config['name'] ?? 'SLGGH_SESSID';

        if (!headers_sent() && session_status() !== PHP_SESSION_ACTIVE) {
            session_name($sessionName);

            // Security session ini settings
            ini_set('session.use_only_cookies', '1');
            ini_set('session.use_strict_mode', '1');
            ini_set('session.cookie_httponly', $httponly ? '1' : '0');
            ini_set('session.cookie_samesite', $samesite);
            if ($secure) {
                ini_set('session.cookie_secure', '1');
            }
        }

        if (!headers_sent()) {
            session_set_cookie_params([
                'lifetime' => $lifetime,
                'path' => '/',
                'domain' => '',
                'secure' => $secure,
                'httponly' => $httponly,
                'samesite' => $samesite,
            ]);
        }

        if (session_status() !== PHP_SESSION_ACTIVE) {
            @session_start();
        }
        self::$started = true;

        // Inactivity timeout handling
        $now = time();
        if (isset($_SESSION['_last_activity']) && ($now - $_SESSION['_last_activity'] > $lifetime)) {
            self::destroy();
            if (session_status() !== PHP_SESSION_ACTIVE) {
                @session_start();
            }
            $_SESSION['_flash']['warning'] = 'Your session has timed out due to inactivity. Please log in again.';
        }
        $_SESSION['_last_activity'] = $now;

        // Age check for periodic session ID rotation
        if (!isset($_SESSION['_created_time'])) {
            $_SESSION['_created_time'] = $now;
        } elseif ($now - $_SESSION['_created_time'] > 1800) { // Rotate every 30 mins
            self::regenerate();
            $_SESSION['_created_time'] = $now;
        }
    }

    public static function set(string $key, mixed $value): void
    {
        self::ensureStarted();
        $_SESSION[$key] = $value;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        self::ensureStarted();
        return $_SESSION[$key] ?? $default;
    }

    public static function has(string $key): bool
    {
        self::ensureStarted();
        return isset($_SESSION[$key]);
    }

    public static function remove(string $key): void
    {
        self::ensureStarted();
        unset($_SESSION[$key]);
    }

    public static function regenerate(bool $deleteOldSession = true): bool
    {
        self::ensureStarted();
        if (headers_sent()) {
            return true;
        }
        return @session_regenerate_id($deleteOldSession);
    }

    public static function destroy(): void
    {
        self::ensureStarted();
        $_SESSION = [];

        if (!headers_sent() && ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            @setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            @session_destroy();
        }
        self::$started = false;
    }

    public static function setFlash(string $key, string $message): void
    {
        self::ensureStarted();
        $_SESSION['_flash'][$key] = $message;
    }

    public static function getFlash(string $key, mixed $default = null): mixed
    {
        self::ensureStarted();
        if (isset($_SESSION['_flash'][$key])) {
            $msg = $_SESSION['_flash'][$key];
            unset($_SESSION['_flash'][$key]);
            return $msg;
        }
        return $default;
    }

    public static function hasFlash(string $key): bool
    {
        self::ensureStarted();
        return isset($_SESSION['_flash'][$key]);
    }

    private static function ensureStarted(): void
    {
        if (!self::$started && session_status() !== PHP_SESSION_ACTIVE) {
            self::init();
        }
    }
}
