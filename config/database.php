<?php
declare(strict_types=1);

/**
 * Sheth L.G. General Hospital - Database Configuration (PDO)
 * Supports environment variables, local credentials file, and Hostinger defaults.
 */

// Check for local credentials override file (ideal for Hostinger File Manager where .env may be hidden)
$credsFile = __DIR__ . '/db_credentials.php';
$localCreds = file_exists($credsFile) ? (require $credsFile) : [];
if (!is_array($localCreds)) {
    $localCreds = [];
}

return [
    'driver' => 'mysql',
    'host' => $localCreds['DB_HOST'] ?? $_ENV['DB_HOST'] ?? 'localhost',
    'port' => (int)($localCreds['DB_PORT'] ?? $_ENV['DB_PORT'] ?? 3306),
    'database' => $localCreds['DB_NAME'] ?? $_ENV['DB_NAME'] ?? 'u456035299_hospital',
    'username' => $localCreds['DB_USER'] ?? $_ENV['DB_USER'] ?? 'u456035299_hospital',
    'password' => $localCreds['DB_PASSWORD'] ?? $_ENV['DB_PASSWORD'] ?? '',
    'charset' => $localCreds['DB_CHARSET'] ?? $_ENV['DB_CHARSET'] ?? 'utf8mb4',
    'collation' => $localCreds['DB_COLLATION'] ?? $_ENV['DB_COLLATION'] ?? 'utf8mb4_unicode_ci',
    'options' => (function() {
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_PERSISTENT => false,
        ];
        if (defined('PDO::MYSQL_ATTR_INIT_COMMAND')) {
            $options[PDO::MYSQL_ATTR_INIT_COMMAND] = "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci, time_zone = '+05:30'";
        }
        return $options;
    })(),
];
