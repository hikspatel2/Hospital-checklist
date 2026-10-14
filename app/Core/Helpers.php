<?php
declare(strict_types=1);

use App\Core\App;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;

if (!function_exists('app')) {
    function app(): App
    {
        return App::getInstance();
    }
}

if (!function_exists('config')) {
    function config(string $key, mixed $default = null): mixed
    {
        return app()->getConfig($key, $default);
    }
}

if (!function_exists('db')) {
    function db(): PDO
    {
        return Database::getConnection();
    }
}

if (!function_exists('view')) {
    function view(string $view, array $data = [], ?string $layout = 'layouts/main'): Response
    {
        return View::render($view, $data, $layout);
    }
}

if (!function_exists('redirect')) {
    function redirect(string $url, int $statusCode = 302): void
    {
        Response::redirect($url, $statusCode);
    }
}

if (!function_exists('url')) {
    function url(string $path = ''): string
    {
        $base = rtrim(config('app.url', ''), '/');
        $path = '/' . ltrim($path, '/');
        return $base . $path;
    }
}

if (!function_exists('asset')) {
    function asset(string $path): string
    {
        return url('/assets/' . ltrim($path, '/'));
    }
}

if (!function_exists('e')) {
    /**
     * Escape HTML output for XSS protection
     */
    function e(?string $value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        return Csrf::getToken();
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return Csrf::field();
    }
}

if (!function_exists('session')) {
    function session(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return Session::class;
        }
        return Session::get($key, $default);
    }
}

if (!function_exists('flash')) {
    function flash(string $key, ?string $message = null): mixed
    {
        if ($message !== null) {
            Session::setFlash($key, $message);
            return null;
        }
        return Session::getFlash($key);
    }
}

if (!function_exists('now_kolkata')) {
    /**
     * Get current timestamp formatted in Asia/Kolkata timezone
     */
    function now_kolkata(string $format = 'Y-m-d H:i:s'): string
    {
        $dt = new DateTime('now', new DateTimeZone('Asia/Kolkata'));
        return $dt->format($format);
    }
}

if (!function_exists('format_datetime')) {
    /**
     * Format a stored UTC or database timestamp into readable Asia/Kolkata display time
     */
    function format_datetime(?string $timestamp, string $format = 'd M Y, h:i A'): string
    {
        if (empty($timestamp)) {
            return '—';
        }
        try {
            $dt = new DateTime($timestamp, new DateTimeZone('Asia/Kolkata'));
            return $dt->format($format);
        } catch (\Exception) {
            return $timestamp;
        }
    }
}

if (!function_exists('json_response')) {
    function json_response(mixed $data, int $statusCode = 200): Response
    {
        return Response::json($data, $statusCode);
    }
}

if (!function_exists('auth')) {
    function auth(): ?array
    {
        return \App\Core\Auth::user();
    }
}

if (!function_exists('is_admin')) {
    function is_admin(): bool
    {
        return \App\Core\Auth::isAdmin();
    }
}

if (!function_exists('is_supervisor')) {
    function is_supervisor(): bool
    {
        return \App\Core\Auth::isSupervisor();
    }
}
