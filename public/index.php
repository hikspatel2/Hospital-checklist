<?php
declare(strict_types=1);

/**
 * ==============================================================================
 * Sheth L.G. General Hospital, Ahmedabad
 * Multi-Floor Inspection & Complaint Facility Management System
 * ==============================================================================
 * Public Entry Point
 */

define('APP_START_TIME', microtime(true));

$basePath = dirname(__DIR__);

// Zero-dependency PSR-4 Autoloader for App namespace
spl_autoload_register(function (string $class) use ($basePath): void {
    $prefix = 'App\\';
    $len = strlen($prefix);

    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relativeClass = substr($class, $len);
    $file = $basePath . '/app/' . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});

// Load helper functions
require_once $basePath . '/app/Core/Helpers.php';

// Bootstrap and run application
$app = new App\Core\App($basePath);
$app->run();
