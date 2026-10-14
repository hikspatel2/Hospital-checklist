<?php
declare(strict_types=1);

/**
 * ==============================================================================
 * Sheth L.G. General Hospital - Production Administrator Account Bootstrap
 * ==============================================================================
 * Usage:
 *   php scripts/create_admin.php
 *   php scripts/create_admin.php --name="Admin Name" --email="admin@shethlg.org" --phone="9876543210" --password="StrongPassword123!"
 * 
 * Creates or updates an active Administrator account without hardcoded credentials.
 */

$basePath = dirname(__DIR__);
require_once $basePath . '/app/Core/App.php';
require_once $basePath . '/app/Core/Helpers.php';

use App\Core\App;
use App\Core\Database;
use App\Models\AuditLog;

$app = new App($basePath);

echo "\n====================================================================\n";
echo " SHETH L.G. GENERAL HOSPITAL - SECURE ADMIN BOOTSTRAP (PHASE 12)\n";
echo "====================================================================\n\n";

// Parse CLI Options
$longOpts = [
    'name::',
    'email::',
    'phone::',
    'password::',
    'help',
];
$options = getopt('', $longOpts);

if (isset($options['help'])) {
    echo "Usage: php scripts/create_admin.php [options]\n";
    echo "Options:\n";
    echo "  --name=<string>       Full Name of Administrator\n";
    echo "  --email=<email>       Official Hospital Email Address\n";
    echo "  --phone=<number>      10-digit Contact Phone Number\n";
    echo "  --password=<secret>   Secure Password (min 10 chars, uppercase, lowercase, digit, symbol)\n";
    echo "  --help                Display this assistance message\n\n";
    exit(0);
}

function prompt(string $message): string {
    echo "{$message}: ";
    $handle = fopen('php://stdin', 'r');
    $line = fgets($handle);
    fclose($handle);
    return trim((string)$line);
}

$name = $options['name'] ?? null;
$email = $options['email'] ?? null;
$phone = $options['phone'] ?? null;
$password = $options['password'] ?? null;

// Interactive fallback if missing
if (empty($name)) {
    $name = prompt('Enter Administrator Full Name');
}
if (empty($email)) {
    $email = prompt('Enter Administrator Email Address');
}
if (empty($phone)) {
    $phone = prompt('Enter Administrator Phone Number (10 digits)');
}
if (empty($password)) {
    $password = prompt('Enter Secure Password (min 10 chars, mix of case, digits, symbols)');
}

// 1. Validation
if (empty($name) || strlen($name) < 3) {
    echo "\n[ERROR] Name must be at least 3 characters long.\n";
    exit(1);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo "\n[ERROR] Invalid email address format: {$email}\n";
    exit(1);
}

$cleanPhone = preg_replace('/[^0-9]/', '', (string)$phone);
if (strlen($cleanPhone) < 10) {
    echo "\n[ERROR] Phone number must contain at least 10 digits.\n";
    exit(1);
}

// Password Complexity Enforcement
if (strlen($password) < 10) {
    echo "\n[ERROR] Password must be at least 10 characters long.\n";
    exit(1);
}
if (!preg_match('/[A-Z]/', $password)) {
    echo "\n[ERROR] Password must contain at least one uppercase letter (A-Z).\n";
    exit(1);
}
if (!preg_match('/[a-z]/', $password)) {
    echo "\n[ERROR] Password must contain at least one lowercase letter (a-z).\n";
    exit(1);
}
if (!preg_match('/[0-9]/', $password)) {
    echo "\n[ERROR] Password must contain at least one numeric digit (0-9).\n";
    exit(1);
}
if (!preg_match('/[^A-Za-z0-9]/', $password)) {
    echo "\n[ERROR] Password must contain at least one special character symbol.\n";
    exit(1);
}

try {
    $pdo = Database::getConnection();

    // Check if user already exists
    $checkStmt = $pdo->prepare("SELECT `id`, `name`, `role`, `status` FROM `users` WHERE `email` = :email LIMIT 1");
    $checkStmt->execute(['email' => $email]);
    $existing = $checkStmt->fetch(\PDO::FETCH_ASSOC);

    $passwordHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    $now = date('Y-m-d H:i:s');

    if ($existing) {
        $userId = (int)$existing['id'];
        $updateStmt = $pdo->prepare("
            UPDATE `users` 
            SET `name` = :name, 
                `phone` = :phone, 
                `password_hash` = :hash, 
                `role` = 'admin', 
                `status` = 'active', 
                `updated_at` = :updated_at 
            WHERE `id` = :id
        ");
        $updateStmt->execute([
            'name' => $name,
            'phone' => $cleanPhone,
            'hash' => $passwordHash,
            'updated_at' => $now,
            'id' => $userId,
        ]);

        echo "\n[OK] Existing account updated and promoted to Administrator!\n";
        $action = 'ADMIN_ACCOUNT_UPDATED';
    } else {
        $insertStmt = $pdo->prepare("
            INSERT INTO `users` (`name`, `email`, `phone`, `password_hash`, `role`, `status`, `created_at`, `updated_at`)
            VALUES (:name, :email, :phone, :hash, 'admin', 'active', :created_at, :updated_at)
        ");
        $insertStmt->execute([
            'name' => $name,
            'email' => $email,
            'phone' => $cleanPhone,
            'hash' => $passwordHash,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $userId = (int)$pdo->lastInsertId();

        echo "\n[OK] New Administrator account successfully created!\n";
        $action = 'ADMIN_ACCOUNT_CREATED';
    }

    // Record audit event safely
    AuditLog::record(
        $userId,
        'ADMIN_BOOTSTRAP',
        'users',
        $userId,
        null,
        ['email' => $email, 'role' => 'admin', 'status' => 'active', 'action' => $action]
    );

    // Clear password from memory
    unset($password, $passwordHash);

    echo "--------------------------------------------------------------------\n";
    echo " User ID:    {$userId}\n";
    echo " Full Name:  {$name}\n";
    echo " Email:      {$email}\n";
    echo " Phone:      {$cleanPhone}\n";
    echo " Role:       admin\n";
    echo " Status:     active\n";
    echo " Timestamp:  {$now} (Asia/Kolkata)\n";
    echo "--------------------------------------------------------------------\n";
    echo "Security Notice: Password has been hashed with bcrypt cost 12.\n";
    echo "Audit log recorded without sensitive credential exposure.\n\n";

    exit(0);

} catch (\Throwable $e) {
    echo "\n[FAIL] Failed to create or update administrator: " . $e->getMessage() . "\n";
    exit(1);
}
