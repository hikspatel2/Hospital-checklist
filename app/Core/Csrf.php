<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Cross-Site Request Forgery (CSRF) Protection Service
 */
class Csrf
{
    private const TOKEN_KEY = '_csrf_token';

    /**
     * Generate or retrieve existing CSRF token
     */
    public static function getToken(): string
    {
        $token = Session::get(self::TOKEN_KEY);
        if (!$token || !is_string($token)) {
            $token = bin2hex(random_bytes(32));
            Session::set(self::TOKEN_KEY, $token);
        }
        return $token;
    }

    /**
     * Alias for getToken()
     */
    public static function token(): string
    {
        return self::getToken();
    }

    /**
     * Regenerate CSRF token
     */
    public static function regenerate(): string
    {
        $token = bin2hex(random_bytes(32));
        Session::set(self::TOKEN_KEY, $token);
        return $token;
    }

    /**
     * Validate an incoming token against the session token
     */
    public static function validate(?string $token): bool
    {
        $sessionToken = Session::get(self::TOKEN_KEY);
        if (!is_string($sessionToken) || $sessionToken === '') {
            return false;
        }

        if (!is_string($token) || $token === '') {
            return false;
        }

        return hash_equals($sessionToken, $token);
    }

    /**
     * Render hidden HTML form input
     */
    public static function field(): string
    {
        $token = self::getToken();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }

    /**
     * Verify CSRF token for request or throw exception
     */
    public static function verifyRequest(Request $request): void
    {
        $method = strtoupper($request->getMethod());
        if (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            $token = $request->input('csrf_token') 
                  ?? $request->header('X-CSRF-TOKEN') 
                  ?? $request->header('X-XSRF-TOKEN');

            if (!self::validate($token)) {
                Logger::warning('CSRF validation failure', [
                    'ip' => $request->getIp(),
                    'path' => $request->getPath(),
                    'method' => $method,
                ]);

                throw new \RuntimeException('Invalid or expired CSRF security token. Please refresh the page and try again.', 419);
            }
        }
    }
}
