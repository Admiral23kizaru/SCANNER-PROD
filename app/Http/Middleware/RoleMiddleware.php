<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (!$request->user()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $userRole = $request->user()->role?->name;

        if ($userRole === null || !in_array($userRole, $roles, true)) {
            \Illuminate\Support\Facades\Log::warning("Role Forbidden for User ID: {$request->user()->id} ({$request->user()->email}). Has role: '" . ($userRole ?? 'NONE') . "'. Expected one of: " . implode(', ', $roles));
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        return $next($request);
    }
}
