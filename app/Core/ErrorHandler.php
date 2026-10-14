<?php
declare(strict_types=1);

namespace App\Core;

use ErrorException;
use Throwable;

/**
 * Global Error and Exception Handler
 */
class ErrorHandler
{
    private static bool $debug = false;

    public static function register(bool $debug = false): void
    {
        self::$debug = $debug;

        error_reporting(E_ALL);

        set_error_handler([self::class, 'handleError']);
        set_exception_handler([self::class, 'handleException']);
        register_shutdown_function([self::class, 'handleShutdown']);

        ini_set('display_errors', $debug ? '1' : '0');
        ini_set('log_errors', '1');
    }

    public static function handleError(int $level, string $message, string $file = '', int $line = 0): bool
    {
        if (!(error_reporting() & $level)) {
            return false;
        }

        throw new ErrorException($message, 0, $level, $file, $line);
    }

    public static function handleException(Throwable $e): void
    {
        $code = $e->getCode();
        $statusCode = ($code >= 400 && $code < 600) ? (int)$code : 500;

        Logger::critical("Unhandled Exception: {$e->getMessage()}", [
            'type' => get_class($e),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
        ]);

        if (!headers_sent()) {
            http_response_code($statusCode);
        }

        if (Request::isAjax()) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'error' => self::$debug ? $e->getMessage() : 'An unexpected server error occurred. Please contact hospital support.',
            ]);
            exit;
        }

        self::renderErrorView($statusCode, $e);
        exit;
    }

    public static function handleShutdown(): void
    {
        $error = error_get_last();
        if ($error !== null && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE], true)) {
            self::handleException(new ErrorException(
                $error['message'],
                0,
                $error['type'],
                $error['file'],
                $error['line']
            ));
        }
    }

    private static function renderErrorView(int $statusCode, Throwable $e): void
    {
        $debug = self::$debug;
        $title = $statusCode === 404 ? '404 - Page Not Found' : '500 - Server Error';
        $message = $statusCode === 404 ? 'The requested hospital facility resource or page could not be found.' : 'An internal system error occurred. The incident has been recorded in the audit logs.';

        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title><?= htmlspecialchars($title) ?> - Sheth L.G. General Hospital</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
            <style>
                body { background-color: #f4f6f9; font-family: 'Segoe UI', system-ui, -apple-system, sans-serif; color: #1e293b; min-height: 100vh; display: flex; align-items: center; justify-content: center; }
                .error-card { max-width: 650px; width: 90%; background: #ffffff; border-radius: 12px; box-shadow: 0 10px 25px rgba(15, 23, 42, 0.08); overflow: hidden; border: 1px solid #e2e8f0; }
                .error-header { background-color: #0f2c59; color: #ffffff; padding: 24px; text-align: center; }
                .error-header h1 { font-size: 1.5rem; margin: 0; font-weight: 700; letter-spacing: -0.025em; }
                .error-header p { font-size: 0.875rem; margin: 6px 0 0; color: #cbd5e1; }
                .error-body { padding: 32px 28px; }
                .error-code { font-size: 3rem; font-weight: 800; color: #dc2626; line-height: 1; margin-bottom: 12px; }
                .trace-box { background: #0f172a; color: #38bdf8; padding: 16px; border-radius: 6px; font-family: monospace; font-size: 0.8rem; max-height: 250px; overflow-y: auto; white-space: pre-wrap; margin-top: 18px; }
                .btn-navy { background-color: #0f2c59; color: #fff; font-weight: 600; padding: 10px 24px; border-radius: 6px; text-decoration: none; display: inline-block; }
                .btn-navy:hover { background-color: #1e3a8a; color: #fff; }
            </style>
        </head>
        <body>
            <div class="error-card">
                <div class="error-header">
                    <h1>Sheth L.G. General Hospital, Ahmedabad</h1>
                    <p>Multi-Floor Inspection & Complaint Facility Management</p>
                </div>
                <div class="error-body text-center">
                    <div class="error-code"><?= $statusCode ?></div>
                    <h3 class="h5 fw-bold mb-3"><?= htmlspecialchars($title) ?></h3>
                    <p class="text-secondary mb-4"><?= htmlspecialchars($message) ?></p>

                    <?php if ($debug): ?>
                        <div class="text-start alert alert-danger">
                            <strong>Debug Exception:</strong> <?= htmlspecialchars($e->getMessage()) ?><br>
                            <small>In <?= htmlspecialchars($e->getFile()) ?>:<?= $e->getLine() ?></small>
                            <div class="trace-box"><?= htmlspecialchars($e->getTraceAsString()) ?></div>
                        </div>
                    <?php endif; ?>

                    <?php if (str_contains($e->getMessage(), 'Database Connection Error') || str_contains($e->getMessage(), 'Access denied') || str_contains($e->getMessage(), 'refused')): ?>
                        <div class="text-start alert alert-warning border-warning shadow-sm my-3 p-3">
                            <h6 class="fw-bold text-dark mb-1">⚙️ Hostinger Setup Required</h6>
                            <p class="small text-muted mb-2">The system cannot connect to the MySQL database. Run the 1-Click Setup Wizard to configure your Hostinger database credentials and initialize all tables automatically.</p>
                            <a href="/install.php" class="btn btn-warning btn-sm fw-bold">🚀 Run 1-Click Hostinger Setup (/install.php)</a>
                        </div>
                    <?php endif; ?>

                    <div class="mt-4">
                        <a href="/install.php" class="btn btn-primary me-2">1-Click Setup Wizard</a>
                        <a href="/" class="btn btn-navy me-2">Back to Dashboard</a>
                        <a href="/health" class="btn btn-outline-secondary">System Health</a>
                    </div>
                </div>
            </div>
        </body>
        </html>
        <?php
    }
}
