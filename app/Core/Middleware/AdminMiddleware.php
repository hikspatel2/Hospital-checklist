<?php
declare(strict_types=1);

namespace App\Core\Middleware;

use App\Core\Auth;
use App\Core\Request;
use App\Models\AuditLog;

class AdminMiddleware
{
    public static function handle(Request $request): void
    {
        AuthMiddleware::handle($request);

        if (!Auth::isAdmin()) {
            AuditLog::record(
                Auth::id(),
                'UNAUTHORIZED_ADMIN_ROUTE_ATTEMPT',
                'system',
                null,
                null,
                ['attempted_path' => $request->getPath(), 'user_role' => Auth::role()],
                $request->getIp(),
                $_SERVER['HTTP_USER_AGENT'] ?? ''
            );

            throw new \RuntimeException('Access Denied: Administrator privileges are required to perform this action.', 403);
        }
    }
}
