<?php
declare(strict_types=1);

namespace App\Core\Middleware;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;

class AuthMiddleware
{
    public static function handle(Request $request): void
    {
        if (!Auth::check()) {
            if (Request::isAjax()) {
                Response::json(['error' => 'Authentication required'], 401)->send();
                exit;
            }

            Session::setFlash('warning', 'Please sign in to access the hospital facility portal.');
            Response::redirect('/login');
        }
    }
}
