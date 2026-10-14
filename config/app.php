<?php
declare(strict_types=1);

/**
 * Sheth L.G. General Hospital - Application Configuration
 */

return [
    'name' => $_ENV['APP_NAME'] ?? 'Sheth L.G. General Hospital',
    'subtitle' => $_ENV['APP_SUBTITLE'] ?? 'Multi-Floor Inspection & Complaint Facility Management',
    'env' => $_ENV['APP_ENV'] ?? 'production',
    'debug' => filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN),
    'display_errors' => false, // Production hardening: Never leak stack traces to HTTP clients
    'url' => rtrim($_ENV['APP_URL'] ?? 'http://localhost', '/'),
    'timezone' => $_ENV['APP_TIMEZONE'] ?? 'Asia/Kolkata',
    'key' => $_ENV['APP_KEY'] ?? '',

    'session' => [
        'lifetime' => (int)($_ENV['SESSION_LIFETIME_MINUTES'] ?? 60),
        'secure' => filter_var($_ENV['SESSION_SECURE_COOKIE'] ?? false, FILTER_VALIDATE_BOOLEAN),
        'httponly' => filter_var($_ENV['SESSION_HTTPONLY'] ?? true, FILTER_VALIDATE_BOOLEAN),
        'samesite' => $_ENV['SESSION_SAMESITE'] ?? 'Lax',
        'name' => 'SLGGH_SESSID',
    ],

    'storage' => [
        'logs' => dirname(__DIR__) . '/storage/logs',
        'backups' => dirname(__DIR__) . '/storage/backups',
        'uploads' => dirname(__DIR__) . '/storage/uploads',
        'max_file_size_mb' => (int)($_ENV['UPLOAD_MAX_FILESIZE_MB'] ?? 10),
        'allowed_mimes' => explode(',', $_ENV['ALLOWED_UPLOAD_MIME_TYPES'] ?? 'image/jpeg,image/png,image/webp,application/pdf'),
    ],

    'hospital' => [
        'default_latitude' => (float)($_ENV['DEFAULT_HOSPITAL_LATITUDE'] ?? 22.9984),
        'default_longitude' => (float)($_ENV['DEFAULT_HOSPITAL_LONGITUDE'] ?? 72.6025),
        'max_gps_radius_meters' => (int)($_ENV['GPS_MAX_ALLOWED_RADIUS_METERS'] ?? 150),
    ],
];
