<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SanctumTokenFromQuery
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->headers->has('Authorization')) {
            $token = $request->query('token');
            if (is_string($token) && $token !== '') {
                $value = 'Bearer ' . $token;
                $request->headers->set('Authorization', $value);
                $_SERVER['HTTP_AUTHORIZATION'] = $value;
            }
        }
        
        if ($request->is('api/*')) {
            $request->headers->set('Accept', 'application/json');
        }

        return $next($request);
    }
}

