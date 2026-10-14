<?php
declare(strict_types=1);

/**
 * CLI Development Database Seeder
 * Usage: php scripts/seed.php
 */

require_once dirname(__DIR__) . '/app/Core/App.php';
require_once dirname(__DIR__) . '/app/Core/Helpers.php';

use App\Core\App;
use App\Core\Database;
use App\Core\Logger;

$app = new App(dirname(__DIR__));

echo "\n======================================================\n";
echo " Sheth L.G. General Hospital - Database Seeder (Demo)\n";
echo "======================================================\n\n";

if (config('app.env') === 'production') {
    echo "[ABORTED] Seeder cannot be run in production environment.\n";
    exit(1);
}

try {
    $pdo = Database::getConnection();

    // 1. Run raw seed SQL
    $seedSqlFile = dirname(__DIR__) . '/database/seeds/dev_seed.sql';
    if (!file_exists($seedSqlFile)) {
        throw new \RuntimeException("Seed file not found: {$seedSqlFile}");
    }

    $sql = file_get_contents($seedSqlFile);
    $pdo->exec($sql);
    echo " [OK] Base seed tables populated.\n";

    // 2. Ensure users have fresh, guaranteed-valid bcrypt hashes
    $adminPasswordHash = password_hash('Admin@12345', PASSWORD_BCRYPT, ['cost' => 12]);
    $supPasswordHash   = password_hash('Supervisor@123', PASSWORD_BCRYPT, ['cost' => 12]);

    $updateStmt = $pdo->prepare("UPDATE `users` SET `password_hash` = :hash WHERE `email` = :email");
    $updateStmt->execute(['hash' => $adminPasswordHash, 'email' => 'admin@shethlg.local']);
    $updateStmt->execute(['hash' => $supPasswordHash, 'email' => 'supervisor1@shethlg.local']);
    $updateStmt->execute(['hash' => $supPasswordHash, 'email' => 'supervisor2@shethlg.local']);

    echo " [OK] Demo user password hashes verified.\n";
    echo "\n------------------------------------------------------\n";
    echo " DEMO CREDENTIALS:\n";
    echo " 1. Administrator:\n";
    echo "    Email:    admin@shethlg.local\n";
    echo "    Password: Admin@12345\n";
    echo "    Role:     admin\n\n";
    echo " 2. Supervisor 1:\n";
    echo "    Email:    supervisor1@shethlg.local\n";
    echo "    Password: Supervisor@123\n";
    echo "    Role:     supervisor\n\n";
    echo " 3. Supervisor 2:\n";
    echo "    Email:    supervisor2@shethlg.local\n";
    echo "    Password: Supervisor@123\n";
    echo "    Role:     supervisor\n";
    echo "------------------------------------------------------\n\n";

    Logger::info('Development seed data applied successfully.');

} catch (\Throwable $e) {
    echo "\n[ERROR] Seeding failed: " . $e->getMessage() . "\n";
    Logger::critical("Seeding error: {$e->getMessage()}");
    exit(1);
}
