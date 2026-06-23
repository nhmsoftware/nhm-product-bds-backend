<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        // Convert user role to string for comparison
        $userRoleValue = (string) ($user->role->value ?? $user->role);

        if (!in_array($userRoleValue, $roles)) {
            return response()->json(['message' => 'Permission denied.'], 403);
        }

        return $next($request);
    }
}
