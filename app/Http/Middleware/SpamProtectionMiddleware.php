<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class SpamProtectionMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // Only apply to reservation creation (both API and web routes)
        $isReservationRoute = $request->is('api/*/reservations') || 
                             $request->is('api/reservations') ||
                             $request->is('*/reservations');
        
        if ($isReservationRoute && $request->isMethod('post')) {
            $ip = $request->ip();
            $key = 'reservation_spam_' . $ip;
            
            // Limit to 3 reservations per hour per IP
            $attempts = Cache::get($key, 0);
            
            if ($attempts >= 3) {
                if ($request->expectsJson() || $request->is('api/*')) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Too many reservation attempts. Please try again later.',
                    ], 429);
                }
                
                return redirect()->back()->with('error', 'Too many reservation attempts. Please try again later.');
            }
            
            Cache::put($key, $attempts + 1, now()->addHour());
        }

        return $next($request);
    }
}

