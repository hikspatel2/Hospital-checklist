<?php
declare(strict_types=1);

namespace App\Core\Middleware;

use App\Core\Auth;
use App\Core\Request;

class SupervisorMiddleware
{
    public static function handle(Request $request): void
    {
        AuthMiddleware::handle($request);

        if (!Auth::isSupervisor() && !Auth::isAdmin()) {
            throw new \RuntimeException('Access Denied: Facility supervisor or administrator privileges required.', 403);
        }
    }
}
