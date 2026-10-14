<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Append-only application logger writing to daily rotated files in storage/logs
 */
class Logger
{
    public const EMERGENCY = 'EMERGENCY';
    public const ALERT     = 'ALERT';
    public const CRITICAL  = 'CRITICAL';
    public const ERROR     = 'ERROR';
    public const WARNING   = 'WARNING';
    public const NOTICE    = 'NOTICE';
    public const INFO      = 'INFO';
    public const DEBUG     = 'DEBUG';

    private static array $sensitiveKeys = [
        'password', 'password_confirmation', 'secret', 'token', 'csrf_token',
        'api_key', 'auth_token', 'private_key', 'authorization', 'card_number'
    ];

    public static function emergency(string $message, array $context = []): void
    {
        self::log(self::EMERGENCY, $message, $context);
    }

    public static function alert(string $message, array $context = []): void
    {
        self::log(self::ALERT, $message, $context);
    }

    public static function critical(string $message, array $context = []): void
    {
        self::log(self::CRITICAL, $message, $context);
    }

    public static function error(string $message, array $context = []): void
    {
        self::log(self::ERROR, $message, $context);
    }

    public static function warning(string $message, array $context = []): void
    {
        self::log(self::WARNING, $message, $context);
    }

    public static function notice(string $message, array $context = []): void
    {
        self::log(self::NOTICE, $message, $context);
    }

    public static function info(string $message, array $context = []): void
    {
        self::log(self::INFO, $message, $context);
    }

    public static function debug(string $message, array $context = []): void
    {
        self::log(self::DEBUG, $message, $context);
    }

    public static function log(string $level, string $message, array $context = []): void
    {
        $logDir = dirname(__DIR__, 2) . '/storage/logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }

        $date = date('Y-m-d');
        $filePath = "{$logDir}/app-{$date}.log";

        $sanitizedContext = self::maskSensitiveData($context);
        $contextString = !empty($sanitizedContext) ? ' ' . json_encode($sanitizedContext, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : '';

        $timestamp = date('Y-m-d H:i:s T');
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'CLI';
        $formatted = sprintf("[%s] [%s] [%s] %s%s%s", $timestamp, $ip, $level, $message, $contextString, PHP_EOL);

        @file_put_contents($filePath, $formatted, FILE_APPEND | LOCK_EX);
    }

    private static function maskSensitiveData(array $data): array
    {
        $clean = [];
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $clean[$key] = self::maskSensitiveData($value);
            } elseif (is_string($key) && in_array(strtolower($key), self::$sensitiveKeys, true)) {
                $clean[$key] = '********';
            } else {
                $clean[$key] = $value;
            }
        }
        return $clean;
    }
}
