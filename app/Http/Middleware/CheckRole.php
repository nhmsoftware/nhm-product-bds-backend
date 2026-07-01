<?php

namespace App\Http\Middleware;

use App\Modules\Auth\Models\Role;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * Hỗ trợ 2 kiểu kiểm tra:
     * - role:name → kiểm tra user có role name đó
     * - permission:name → kiểm tra user có permission đó
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$checks): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        foreach ($checks as $check) {
            if (str_starts_with($check, 'permission:')) {
                $permission = substr($check, strlen('permission:'));
                if ($user->hasPermission($permission)) {
                    return $next($request);
                }
            } elseif (str_starts_with($check, 'role:')) {
                $roleName = substr($check, strlen('role:'));
                if ($user->role?->name === $roleName) {
                    return $next($request);
                }
            } else {
                // Legacy: direct role name
                if ($user->role?->name === $check) {
                    return $next($request);
                }
            }
        }

        return response()->json(['message' => 'Permission denied.'], 403);
    }
}
