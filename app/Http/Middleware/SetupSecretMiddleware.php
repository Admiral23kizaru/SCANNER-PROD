<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SetupSecretMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $secret = $request->header('X-Setup-Secret');
        if (!$secret || $secret !== config('app.setup_secret')) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return $next($request);
    }
}

