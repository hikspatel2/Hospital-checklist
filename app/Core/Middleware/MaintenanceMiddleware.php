<?php
declare(strict_types=1);

namespace App\Core\Middleware;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Models\HospitalSetting;

/**
 * Hospital Maintenance Mode Middleware
 * Restricts non-administrative access during scheduled clinical system maintenance,
 * while ensuring emergency telemetry (/health) and Admin management remain operational.
 */
class MaintenanceMiddleware
{
    private const EXEMPT_PATHS = [
        '/login',
        '/logout',
        '/health',
        '/api/health',
        '/forgot-password',
        '/reset-password',
    ];

    /**
     * Check maintenance mode and handle access
     */
    public static function handle(Request $request): void
    {
        if (!self::isUnderMaintenance()) {
            return;
        }

        $path = $request->getPath();

        // 1. Allow exempt paths
        foreach (self::EXEMPT_PATHS as $exempt) {
            if ($path === $exempt || str_starts_with($path, $exempt . '/')) {
                return;
            }
        }

        // 2. Allow logged-in Administrators to manage and resolve maintenance
        if (Auth::check() && Auth::isAdmin()) {
            return;
        }

        // 3. Render polite Hospital Maintenance Announcement with 503 status
        $noticeMessage = (string)HospitalSetting::get(
            'maintenance_notice_message',
            'Sheth L.G. General Hospital Portal is undergoing scheduled clinical maintenance. For emergency ward coordination, contact the control room at +91-79-25461380.'
        );

        if (Request::isAjax()) {
            $resp = Response::json([
                'success'     => false,
                'maintenance' => true,
                'message'     => $noticeMessage,
            ], 503);
            $resp->send();
            exit;
        }

        http_response_code(503);
        header('Retry-After: 300'); // 5 minutes

        // Include clean maintenance screen HTML
        echo '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance Notice - Sheth L.G. General Hospital</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { background-color: #0f172a; color: #f8fafc; font-family: system-ui, sans-serif; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .card-maintenance { max-width: 580px; width: 100%; border: 1px solid rgba(255,255,255,0.1); background: #1e293b; border-radius: 12px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.5); }
    </style>
</head>
<body>
    <div class="card card-maintenance text-center p-4 p-md-5">
        <div class="mb-3">
            <span class="badge bg-warning bg-opacity-25 text-warning px-3 py-2 fs-6 rounded-pill">
                <i class="bi bi-tools me-1"></i> Clinical Maintenance Mode
            </span>
        </div>
        <h4 class="fw-bold mb-2">Sheth L.G. General Hospital</h4>
        <p class="text-white-50 small mb-4">Multi-Floor Facility Inspection & Complaint Command Center</p>
        
        <div class="bg-dark bg-opacity-50 p-3 rounded-3 border border-secondary border-opacity-25 mb-4 text-start small">
            <div class="fw-semibold text-warning mb-1"><i class="bi bi-megaphone-fill me-1"></i> Facility Broadcast:</div>
            <div class="text-light">' . htmlspecialchars($noticeMessage, ENT_QUOTES, 'UTF-8') . '</div>
        </div>

        <div class="d-flex justify-content-center gap-3">
            <a href="' . url('/login') . '" class="btn btn-primary btn-sm px-4">
                <i class="bi bi-shield-lock-fill me-1"></i> Staff / Admin Portal Login
            </a>
            <a href="' . url('/health') . '" class="btn btn-outline-light btn-sm px-3">
                <i class="bi bi-activity me-1"></i> System Health
            </a>
        </div>
        <div class="mt-4 pt-3 border-top border-secondary border-opacity-25 text-muted small" style="font-size: 0.75rem;">
            AMC Medical Education Trust &bull; Maninagar, Ahmedabad &bull; Standard IST Timezone
        </div>
    </div>
</body>
</html>';
        exit;
    }

    /**
     * Check if maintenance mode setting is enabled
     */
    public static function isUnderMaintenance(): bool
    {
        return (string)HospitalSetting::get('maintenance_mode_enabled', '0') === '1';
    }
}
