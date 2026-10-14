<?php
declare(strict_types=1);

namespace App\Core\Middleware;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;

class GuestMiddleware
{
    public static function handle(Request $request): void
    {
        if (Auth::check()) {
            if (Auth::isSupervisor()) {
                Response::redirect('/supervisor/overview');
            } else {
                Response::redirect('/dashboard');
            }
        }
    }
}
