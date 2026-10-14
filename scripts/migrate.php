<?php
declare(strict_types=1);

/**
 * CLI Database Migration Runner
 * Usage: php scripts/migrate.php
 */

require_once dirname(__DIR__) . '/app/Core/App.php';
require_once dirname(__DIR__) . '/app/Core/Helpers.php';

use App\Core\App;
use App\Core\Database;
use App\Core\Logger;

// Initialize app container in CLI mode
$app = new App(dirname(__DIR__));

echo "\n======================================================\n";
echo " Sheth L.G. General Hospital - Database Migration Runner\n";
echo "======================================================\n\n";

try {
    $pdo = Database::getConnection();

    // 1. Ensure migrations tracking table exists
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `schema_migrations` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `version` VARCHAR(100) NOT NULL UNIQUE,
            `name` VARCHAR(255) NOT NULL,
            `executed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `execution_time_ms` INT UNSIGNED DEFAULT 0
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // 2. Fetch already executed migrations
    $executed = $pdo->query("SELECT `version` FROM `schema_migrations`")->fetchAll(PDO::FETCH_COLUMN);

    // 3. Scan migrations folder
    $migrationsDir = dirname(__DIR__) . '/database/migrations';
    $files = glob("{$migrationsDir}/*.sql");
    sort($files);

    if (empty($files)) {
        echo "No migration files found in {$migrationsDir}\n";
        exit(0);
    }

    $pendingCount = 0;
    foreach ($files as $file) {
        $basename = basename($file);
        $version = pathinfo($basename, PATHINFO_FILENAME);

        if (in_array($version, $executed, true)) {
            echo " [ALREADY RUN] {$basename}\n";
            continue;
        }

        echo " [RUNNING]     {$basename} ... ";
        $pendingCount++;

        $sql = file_get_contents($file);
        if ($sql === false) {
            throw new \RuntimeException("Unable to read migration file: {$file}");
        }

        $startTime = microtime(true);

        // Execute migration
        $pdo->exec($sql);

        $executionTimeMs = (int)round((microtime(true) - $startTime) * 1000);

        // Record in tracking table
        $stmt = $pdo->prepare("INSERT INTO `schema_migrations` (`version`, `name`, `execution_time_ms`) VALUES (:version, :name, :time_ms)");
        $stmt->execute([
            'version' => $version,
            'name' => $basename,
            'time_ms' => $executionTimeMs,
        ]);

        echo "SUCCESS ({$executionTimeMs} ms)\n";
        Logger::info("Database migration executed: {$basename} in {$executionTimeMs}ms");
    }

    if ($pendingCount === 0) {
        echo "\nDatabase is up to date. No pending migrations.\n";
    } else {
        echo "\nSuccessfully applied {$pendingCount} migration(s).\n";
    }

} catch (\Throwable $e) {
    echo "\n\n[ERROR] Migration failed: " . $e->getMessage() . "\n";
    Logger::critical("Migration execution failed: " . $e->getMessage(), [
        'file' => $e->getFile(),
        'line' => $e->getLine(),
    ]);
    exit(1);
}
