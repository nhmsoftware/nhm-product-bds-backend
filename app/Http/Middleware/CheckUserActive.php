<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckUserActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = null;

        try {
            $user = auth('api')->user() ?? $request->user();
        } catch (\Throwable $e) {
            // Implies JWT token is invalid, expired, or malformed.
            // Let the standard auth:api middleware handle the 401 response.
        }

        if ($user !== null && !$user->is_active) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tài khoản của bạn đã bị khóa. Vui lòng liên hệ quản trị viên.',
                ], 403);
            }

            // For web session requests, log out the user and abort
            if (auth()->check()) {
                auth()->logout();
            }
            if (auth('api')->check()) {
                auth('api')->logout();
            }

            abort(403, 'Tài khoản của bạn đã bị khóa. Vui lòng liên hệ quản trị viên.');
        }

        return $next($request);
    }
}
