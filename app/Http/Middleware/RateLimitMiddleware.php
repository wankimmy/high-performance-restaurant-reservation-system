<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class RateLimitMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $identifier = $request->ip();
        $key = 'api_rate_limit_' . $identifier;
        $maxAttempts = 60; // 60 requests per minute
        $decayMinutes = 1;

        $attempts = Cache::get($key, 0);

        if ($attempts >= $maxAttempts) {
            return response()->json([
                'success' => false,
                'message' => 'Too many requests. Please try again later.',
            ], 429);
        }

        Cache::put($key, $attempts + 1, now()->addMinutes($decayMinutes));

        return $next($request);
    }
}

