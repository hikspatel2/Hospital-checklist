<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;

/**
 * Health check controller providing operational telemetry
 * without exposing internal credentials, hostnames, or database names.
 */
class HealthController extends Controller
{
    public function index(Request $request): Response
    {
        $dbCheck = Database::checkConnection();
        $isDbUp = $dbCheck['connected'];

        $logsDir = config('storage.logs') ?? (dirname(__DIR__, 2) . '/storage/logs');
        $backupsDir = config('storage.backups') ?? (dirname(__DIR__, 2) . '/storage/backups');
        $uploadsDir = config('storage.uploads') ?? (dirname(__DIR__, 2) . '/storage/uploads');

        $storageWritable = is_writable($logsDir) && is_writable($backupsDir) && is_writable($uploadsDir);

        $currentTz = date_default_timezone_get();
        $tzOk = ($currentTz === 'Asia/Kolkata');

        $isSystemHealthy = $isDbUp && $storageWritable && $tzOk;

        $healthData = [
            'status' => $isSystemHealthy ? 'healthy' : 'degraded',
            'timestamp' => now_kolkata('Y-m-d H:i:s T'),
            'services' => [
                'application' => 'operational',
                'database' => $isDbUp ? 'connected' : 'disconnected',
                'storage' => $storageWritable ? 'writable' : 'read_only_or_missing',
                'timezone' => $tzOk ? 'Asia/Kolkata (synchronized)' : 'misconfigured',
            ],
            'runtime' => [
                'php_version' => PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION,
                'environment' => config('app.env', 'production'),
            ],
        ];

        if (Request::isAjax() || $request->query('format') === 'json') {
            $statusCode = $isSystemHealthy ? 200 : 503;
            return $this->json($healthData, $statusCode);
        }

        return $this->render('health/index', [
            'health' => $healthData,
            'isSystemHealthy' => $isSystemHealthy,
        ]);
    }
}
