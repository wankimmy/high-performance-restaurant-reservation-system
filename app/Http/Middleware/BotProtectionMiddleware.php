<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class BotProtectionMiddleware
{
    private array $botUserAgents = [
        'bot', 'crawler', 'spider', 'scraper', 'curl', 'wget',
        'python', 'java', 'perl', 'ruby', 'php', 'go-http',
        'headless', 'selenium', 'phantom', 'webdriver',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        // Bypass bot protection for k6 load tests
        if ($request->header('X-k6-Test') === 'true' || 
            str_contains($request->userAgent() ?? '', 'k6-load-test') ||
            str_contains($request->userAgent() ?? '', 'k6')) {
            return $next($request);
        }
        
        $userAgent = strtolower($request->userAgent() ?? '');
        $ip = $request->ip();

        // Check for bot user agents
        foreach ($this->botUserAgents as $botAgent) {
            if (str_contains($userAgent, $botAgent)) {
                // Allow some legitimate bots but rate limit them
                if (!in_array($botAgent, ['googlebot', 'bingbot', 'slurp'])) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Access denied',
                    ], 403);
                }
            }
        }

        // Check for suspicious patterns
        if (empty($userAgent) || $userAgent === 'mozilla/4.0') {
            $suspiciousKey = 'suspicious_ip_' . $ip;
            $suspiciousCount = Cache::get($suspiciousKey, 0);
            
            if ($suspiciousCount > 5) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied',
                ], 403);
            }
            
            Cache::put($suspiciousKey, $suspiciousCount + 1, now()->addHours(1));
        }

        return $next($request);
    }
}

