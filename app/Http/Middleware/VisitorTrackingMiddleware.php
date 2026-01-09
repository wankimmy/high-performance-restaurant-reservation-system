<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Symfony\Component\HttpFoundation\Response;

class VisitorTrackingMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // Skip tracking for admin routes
        if ($request->is('admin/*')) {
            return $next($request);
        }
        
        // Only track visitors on booking page and API
        if ($request->is('/') || $request->is('api/*')) {
            $ip = $request->ip();
            $now = now();
            $key = 'visitor_' . $ip . '_' . $now->format('Y-m-d-H-i');
            
            // Track unique visitor in current minute
            if (!Cache::has($key)) {
                Cache::put($key, true, now()->addMinutes(2));
                
                // Update visitor counts
                $this->updateVisitorCounts($ip);
            }
            
            // Track current active visitors (last 5 minutes)
            $activeKey = 'active_visitor_' . $ip;
            Cache::put($activeKey, true, now()->addMinutes(5));
        }
        
        return $next($request);
    }

    private function updateVisitorCounts(string $ip): void
    {
        $now = now();
        
        try {
            // Use Redis sets for efficient tracking
            $redis = Redis::connection();
            
            // Current visitors (active in last 5 minutes)
            $activeKey = 'active_visitors_set';
            $redis->sadd($activeKey, $ip);
            $redis->expire($activeKey, 300); // 5 minutes
            $activeCount = $redis->scard($activeKey);
            Cache::put('current_visitors', $activeCount, now()->addMinutes(6));
            
            // Visitors in last minute
            $lastMinuteKey = 'visitors_minute_' . $now->format('Y-m-d-H-i');
            $redis->sadd($lastMinuteKey, $ip);
            $redis->expire($lastMinuteKey, 120); // 2 minutes
            $lastMinuteCount = $redis->scard($lastMinuteKey);
            Cache::put('visitors_last_minute', $lastMinuteCount, now()->addMinutes(2));
            
            // Store recent visitor IPs for unique count (last minute)
            $recentKey = 'recent_visitor_ips_' . $now->format('Y-m-d-H-i');
            $redis->sadd($recentKey, $ip);
            $redis->expire($recentKey, 120);
            
            // Visitors in last hour
            $lastHourKey = 'visitors_hour_' . $now->format('Y-m-d-H');
            $redis->sadd($lastHourKey, $ip);
            $redis->expire($lastHourKey, 7200); // 2 hours
            $lastHourCount = $redis->scard($lastHourKey);
            Cache::put('visitors_last_hour', $lastHourCount, now()->addHours(2));
            
            // Visitors today
            $todayKey = 'visitors_today_' . $now->format('Y-m-d');
            $redis->sadd($todayKey, $ip);
            $redis->expire($todayKey, 86400); // 24 hours
            $todayCount = $redis->scard($todayKey);
            Cache::put('visitors_today', $todayCount, now()->addDay());
        } catch (\Exception $e) {
            // Fallback to cache-based tracking if Redis fails
            $this->updateVisitorCountsFallback($ip, $now);
        }
    }
    
    private function updateVisitorCountsFallback(string $ip, $now): void
    {
        // Current visitors (active in last 5 minutes)
        $activeVisitors = Cache::get('active_visitor_ips', []);
        $activeVisitors[] = $ip;
        $activeVisitors = array_unique($activeVisitors);
        // Limit to prevent memory issues
        if (count($activeVisitors) > 10000) {
            $activeVisitors = array_slice($activeVisitors, -10000);
        }
        Cache::put('active_visitor_ips', $activeVisitors, now()->addMinutes(6));
        Cache::put('current_visitors', count($activeVisitors), now()->addMinutes(6));
        
        // Visitors in last minute
        $lastMinuteKey = 'visitors_minute_' . $now->format('Y-m-d-H-i');
        $lastMinuteVisitors = Cache::get($lastMinuteKey, []);
        $lastMinuteVisitors[] = $ip;
        Cache::put($lastMinuteKey, array_unique($lastMinuteVisitors), now()->addMinutes(2));
        Cache::put('visitors_last_minute', count(array_unique($lastMinuteVisitors)), now()->addMinutes(2));
        
        // Store recent visitor IPs for unique count
        $recentIps = Cache::get('recent_visitor_ips', []);
        $recentIps[] = $ip;
        if (count($recentIps) > 1000) {
            $recentIps = array_slice($recentIps, -1000);
        }
        Cache::put('recent_visitor_ips', $recentIps, now()->addMinutes(2));
        
        // Visitors in last hour
        $lastHourKey = 'visitors_hour_' . $now->format('Y-m-d-H');
        $lastHourVisitors = Cache::get($lastHourKey, []);
        $lastHourVisitors[] = $ip;
        Cache::put($lastHourKey, array_unique($lastHourVisitors), now()->addHours(2));
        Cache::put('visitors_last_hour', count(array_unique($lastHourVisitors)), now()->addHours(2));
        
        // Visitors today
        $todayKey = 'visitors_today_' . $now->format('Y-m-d');
        $todayVisitors = Cache::get($todayKey, []);
        $todayVisitors[] = $ip;
        Cache::put($todayKey, array_unique($todayVisitors), now()->addDay());
        Cache::put('visitors_today', count(array_unique($todayVisitors)), now()->addDay());
    }
}
