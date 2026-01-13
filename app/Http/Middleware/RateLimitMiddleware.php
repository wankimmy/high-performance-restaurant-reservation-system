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
        
        // Fixed window: reset at the start of each minute
        // Key includes the current minute timestamp, so it auto-resets every minute
        $currentMinute = now()->format('Y-m-d-H-i');
        $key = 'api_rate_limit_' . $identifier . '_' . $currentMinute;
        $maxAttempts = 60; // 60 requests per minute

        $attempts = Cache::get($key, 0);

        if ($attempts >= $maxAttempts) {
            return response()->json([
                'success' => false,
                'message' => 'Too many requests. Please try again later.',
            ], 429);
        }

        // Store for 2 minutes to ensure cleanup (1 minute for current window + 1 minute buffer)
        Cache::put($key, $attempts + 1, now()->addMinutes(2));

        return $next($request);
    }
}

