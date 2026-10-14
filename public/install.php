<?php
declare(strict_types=1);

/**
 * ==============================================================================
 * SHETH L.G. GENERAL HOSPITAL, AHMEDABAD
 * Multi-Floor Inspection & Complaint Facility Management System
 * 
 * 1-Click Hostinger & Production Web Setup Wizard
 * Accessible via: https://your-domain.com/install.php
 * ==============================================================================
 */

// Define project base path (parent of public/)
$basePath = dirname(__DIR__);

// Check if system is already installed
$lockFile = $basePath . '/storage/installed.lock';
$envFile  = $basePath . '/.env';
$isInstalled = file_exists($lockFile);

// Allow forced re-run only if explicit parameter passed or lock removed
$allowRun = !$isInstalled || (isset($_GET['force']) && $_GET['force'] === '1');

// Pre-flight environment diagnostics
$phpOk = version_compare(PHP_VERSION, '8.1.0', '>=');
$pdoOk = extension_loaded('pdo') && extension_loaded('pdo_mysql');
$mbstringOk = extension_loaded('mbstring');
$opensslOk = extension_loaded('openssl');
$storageWritable = is_writable($basePath . '/storage') || (!file_exists($basePath . '/storage') && is_writable($basePath));
$rootWritable = is_writable($basePath) || (file_exists($envFile) && is_writable($envFile));

// Auto-detect App URL
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$detectedUrl = $protocol . '://' . $host;

$message = null;
$error = null;
$step = 'form'; // 'form' | 'success'

// Handle POST Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $allowRun) {
    $dbHost = trim($_POST['db_host'] ?? 'localhost');
    $dbPort = (int)($_POST['db_port'] ?? 3306);
    $dbName = trim($_POST['db_name'] ?? '');
    $dbUser = trim($_POST['db_user'] ?? '');
    $dbPass = $_POST['db_password'] ?? '';
    $appUrl = rtrim(trim($_POST['app_url'] ?? $detectedUrl), '/');
    $adminPassword = $_POST['admin_password'] ?? 'Admin@12345';
    if (empty($adminPassword)) {
        $adminPassword = 'Admin@12345';
    }

    if (empty($dbName) || empty($dbUser)) {
        $error = "Database Name and Database Username are required.";
    } else {
        try {
            // 1. Test PDO connection
            $dsn = "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4";
            $pdo = new PDO($dsn, $dbUser, $dbPass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);

            // Guarantee Asia/Kolkata timezone and utf8mb4 collation
            $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci, time_zone = '+05:30'");

            // 2. Execute Complete Database Schema (Tables + Constraints + Initial Seeds)
            $schemaFile = $basePath . '/database/schema_complete.sql';
            if (!file_exists($schemaFile)) {
                // Fallback to migrating directory files in order
                $schemaMigrations = glob($basePath . '/database/migrations/*.sql');
                sort($schemaMigrations);
                foreach ($schemaMigrations as $migration) {
                    $sql = file_get_contents($migration);
                    if ($sql) {
                        $pdo->exec($sql);
                    }
                }
                $seedFile = $basePath . '/database/seeds/dev_seed.sql';
                if (file_exists($seedFile)) {
                    $seedSql = file_get_contents($seedFile);
                    if ($seedSql) {
                        $pdo->exec($seedSql);
                    }
                }
            } else {
                $sql = file_get_contents($schemaFile);
                if ($sql) {
                    $pdo->exec($sql);
                }
            }

            // 3. Update Administrator Password Hash
            $adminHash = password_hash($adminPassword, PASSWORD_BCRYPT, ['cost' => 12]);
            $stmt = $pdo->prepare("UPDATE `users` SET `password_hash` = :hash WHERE `email` = 'admin@shethlg.local' OR `role` = 'admin' LIMIT 1");
            $stmt->execute(['hash' => $adminHash]);

            // 4. Generate Random Application Secret Key
            $appKey = 'base64_' . base64_encode(random_bytes(32));

            // 5. Generate and Write Production .env File
            $envContent = <<<ENV
# ==============================================================================
# SHETH L.G. GENERAL HOSPITAL, AHMEDABAD
# Multi-Floor Inspection & Complaint Facility Management System
# Production Environment Configuration
# ==============================================================================

APP_ENV=production
APP_DEBUG=false
APP_NAME="Sheth L.G. General Hospital"
APP_SUBTITLE="Multi-Floor Inspection & Complaint Facility Management"
APP_URL={$appUrl}
APP_TIMEZONE=Asia/Kolkata

APP_KEY={$appKey}

SESSION_LIFETIME_MINUTES=60
SESSION_SECURE_COOKIE=false
SESSION_HTTPONLY=true
SESSION_SAMESITE=Lax

DB_HOST={$dbHost}
DB_PORT={$dbPort}
DB_NAME={$dbName}
DB_USER={$dbUser}
DB_PASSWORD="{$dbPass}"
DB_CHARSET=utf8mb4
DB_COLLATION=utf8mb4_unicode_ci

UPLOAD_MAX_FILESIZE_MB=10
ALLOWED_UPLOAD_MIME_TYPES=image/jpeg,image/png,image/webp,application/pdf

BACKUP_STORAGE_PATH=storage/backups
BACKUP_RETENTION_DAYS=7
BACKUP_ENCRYPTION_KEY=""

DEFAULT_HOSPITAL_LATITUDE=22.9984
DEFAULT_HOSPITAL_LONGITUDE=72.6025
GPS_MAX_ALLOWED_RADIUS_METERS=150
ENV;

            file_put_contents($envFile, $envContent);

            // 6. Ensure Storage Subdirectories Exist
            foreach (['backups', 'logs', 'uploads'] as $folder) {
                $dir = $basePath . '/storage/' . $folder;
                if (!is_dir($dir)) {
                    @mkdir($dir, 0755, true);
                }
            }

            // 7. Write Installation Lock
            $lockData = json_encode([
                'installed_at' => date('Y-m-d H:i:s'),
                'php_version' => PHP_VERSION,
                'app_url' => $appUrl,
                'db_name' => $dbName,
            ], JSON_PRETTY_PRINT);
            @file_put_contents($lockFile, $lockData);

            $step = 'success';
            $message = "Hospital System Database initialized and configured successfully!";

        } catch (PDOException $e) {
            $error = "Database Connection Failed: " . $e->getMessage() . "<br><br><strong>Tips for Hostinger:</strong><br>• Make sure you created the database in Hostinger hPanel -> Databases -> MySQL Databases.<br>• Make sure the database user has been granted all permissions to that database.<br>• Host is usually <code>localhost</code> or <code>127.0.0.1</code>.";
        } catch (\Throwable $e) {
            $error = "Installation Error: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>1-Click Setup - Sheth L.G. General Hospital</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        :root {
            --hospital-navy: #0f2c59;
            --hospital-navy-dark: #091a36;
            --hospital-blue: #0284c7;
        }
        body {
            background-color: #f1f5f9;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            color: #1e293b;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 30px 15px;
        }
        .setup-card {
            max-width: 680px;
            width: 100%;
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 15px 35px rgba(15, 44, 89, 0.12);
            overflow: hidden;
            border: 1px solid #e2e8f0;
        }
        .setup-header {
            background: linear-gradient(135deg, var(--hospital-navy), var(--hospital-navy-dark));
            color: #ffffff;
            padding: 28px 24px;
            text-align: center;
        }
        .setup-header h1 {
            font-size: 1.45rem;
            font-weight: 700;
            margin: 0;
            letter-spacing: -0.02em;
        }
        .setup-header p {
            font-size: 0.85rem;
            margin: 6px 0 0;
            color: #94a3b8;
        }
        .setup-body {
            padding: 30px;
        }
        .btn-navy {
            background-color: var(--hospital-navy);
            border-color: var(--hospital-navy);
            color: #ffffff;
            font-weight: 600;
            padding: 12px 24px;
            border-radius: 6px;
        }
        .btn-navy:hover {
            background-color: var(--hospital-navy-dark);
            border-color: var(--hospital-navy-dark);
            color: #ffffff;
        }
        .form-label {
            font-size: 0.825rem;
            font-weight: 600;
            color: #334155;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }
        .badge-check {
            font-size: 0.8rem;
            padding: 5px 10px;
            border-radius: 4px;
        }
        .cred-box {
            background: #f8fafc;
            border: 1px dashed #cbd5e1;
            border-radius: 8px;
            padding: 16px;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>

<div class="setup-card">
    <div class="setup-header">
        <div class="mb-2">
            <i class="bi bi-hospital fs-1 text-info"></i>
        </div>
        <h1>Sheth L.G. General Hospital, Ahmedabad</h1>
        <p>Hostinger 1-Click Installation & Database Setup Wizard</p>
    </div>

    <div class="setup-body">

        <?php if ($step === 'success'): ?>
            <div class="text-center py-4">
                <div class="text-success mb-3">
                    <i class="bi bi-check-circle-fill display-1"></i>
                </div>
                <h3 class="fw-bold text-success mb-2">Installation Complete!</h3>
                <p class="text-secondary mb-4">
                    The database has been migrated with all hospital tables, foreign keys, floor plan coordinates, and default administrative accounts.
                </p>

                <div class="cred-box text-start mb-4">
                    <h6 class="fw-bold mb-2 text-primary"><i class="bi bi-shield-lock-fill me-1"></i> Default Administrative Login:</h6>
                    <div class="row g-2">
                        <div class="col-sm-6">
                            <strong>Login URL:</strong> <a href="/login" class="text-decoration-none">/login</a>
                        </div>
                        <div class="col-sm-6">
                            <strong>Email:</strong> <code>admin@shethlg.local</code>
                        </div>
                        <div class="col-sm-6">
                            <strong>Password:</strong> <code><?= htmlspecialchars($adminPassword) ?></code>
                        </div>
                        <div class="col-sm-6">
                            <strong>Role:</strong> Administrator
                        </div>
                    </div>
                </div>

                <div class="d-grid gap-2">
                    <a href="/login" class="btn btn-navy btn-lg">
                        <i class="bi bi-box-arrow-in-right me-2"></i> Launch Hospital Management Portal
                    </a>
                </div>
            </div>

        <?php elseif (!$allowRun): ?>
            <div class="text-center py-4">
                <div class="text-primary mb-3">
                    <i class="bi bi-shield-check display-3"></i>
                </div>
                <h4 class="fw-bold">System Already Installed</h4>
                <p class="text-secondary">
                    This hospital system is already configured and operational. To reconfigure, delete <code>storage/installed.lock</code> or run with <code>?force=1</code>.
                </p>
                <div class="mt-4">
                    <a href="/" class="btn btn-navy me-2">Back to Dashboard</a>
                    <a href="/login" class="btn btn-outline-primary">Go to Login</a>
                </div>
            </div>

        <?php else: ?>

            <?php if ($error): ?>
                <div class="alert alert-danger mb-4">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <?= $error ?>
                </div>
            <?php endif; ?>

            <!-- Pre-flight checks bar -->
            <div class="mb-4 p-3 bg-light rounded border">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="small fw-bold text-muted">SERVER READINESS CHECKS:</span>
                    <span class="badge bg-success badge-check">Hostinger Compatible</span>
                </div>
                <div class="d-flex flex-wrap gap-2 small">
                    <span class="badge <?= $phpOk ? 'bg-success' : 'bg-danger' ?>">PHP <?= PHP_VERSION ?></span>
                    <span class="badge <?= $pdoOk ? 'bg-success' : 'bg-danger' ?>">PDO MySQL</span>
                    <span class="badge <?= $mbstringOk ? 'bg-success' : 'bg-danger' ?>">mbstring</span>
                    <span class="badge <?= $opensslOk ? 'bg-success' : 'bg-danger' ?>">openssl</span>
                    <span class="badge <?= $storageWritable ? 'bg-success' : 'bg-danger' ?>">storage/ writable</span>
                </div>
            </div>

            <form method="POST" action="install.php" novalidate>
                <h5 class="fw-bold mb-3 text-navy">
                    <i class="bi bi-database-fill-gear me-1 text-primary"></i> Hostinger MySQL Credentials
                </h5>
                <p class="small text-muted mb-3">
                    Create a MySQL Database in your <strong>Hostinger hPanel &rarr; Databases &rarr; MySQL Databases</strong> and paste the details below:
                </p>

                <div class="row g-3 mb-3">
                    <div class="col-md-8">
                        <label class="form-label" for="db_host">Database Host</label>
                        <input type="text" class="form-control" id="db_host" name="db_host" value="localhost" required>
                        <div class="form-text">Hostinger default is <code>localhost</code> (or <code>127.0.0.1</code>)</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="db_port">Port</label>
                        <input type="number" class="form-control" id="db_port" name="db_port" value="3306" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="db_name">Database Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="db_name" name="db_name" placeholder="e.g. u456035299_hospital" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="db_user">Database Username <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="db_user" name="db_user" placeholder="e.g. u456035299_user" required>
                    </div>

                    <div class="col-12">
                        <label class="form-label" for="db_password">Database Password</label>
                        <input type="password" class="form-control" id="db_password" name="db_password" placeholder="Database password set in Hostinger">
                    </div>
                </div>

                <hr class="my-4">

                <h5 class="fw-bold mb-3 text-navy">
                    <i class="bi bi-gear-fill me-1 text-primary"></i> Application Configuration
                </h5>

                <div class="row g-3 mb-4">
                    <div class="col-12">
                        <label class="form-label" for="app_url">Application URL</label>
                        <input type="url" class="form-control" id="app_url" name="app_url" value="<?= htmlspecialchars($detectedUrl) ?>" required>
                    </div>

                    <div class="col-12">
                        <label class="form-label" for="admin_password">Admin Initial Password</label>
                        <input type="text" class="form-control" id="admin_password" name="admin_password" value="Admin@12345" required>
                        <div class="form-text">Default login: <code>admin@shethlg.local</code></div>
                    </div>
                </div>

                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-navy btn-lg">
                        <i class="bi bi-lightning-charge-fill me-2"></i> Run 1-Click Hostinger Setup
                    </button>
                </div>
            </form>
        <?php endif; ?>

    </div>
</div>

</body>
</html>
