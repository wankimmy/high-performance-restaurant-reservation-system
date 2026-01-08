<?php

use App\Http\Controllers\Api\ReservationController;
use App\Models\RestaurantSetting;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('/availability', [ReservationController::class, 'checkAvailability']);
    Route::get('/closed-dates', [ReservationController::class, 'getClosedDates']);
    Route::get('/restaurant-settings', function () {
        $settings = RestaurantSetting::getSettings();
        return response()->json([
            'success' => true,
            'settings' => [
                'opening_time' => $settings->opening_time,
                'closing_time' => $settings->closing_time,
                'deposit_per_pax' => (float) $settings->deposit_per_pax,
                'time_slot_interval' => (int) ($settings->time_slot_interval ?? 30),
            ],
        ]);
    });
    
    Route::get('/time-slots', function () {
        $settings = RestaurantSetting::getSettings();
        $slots = [];
        
        $openingTime = \Carbon\Carbon::parse($settings->opening_time);
        $closingTime = \Carbon\Carbon::parse($settings->closing_time);
        $interval = $settings->time_slot_interval ?? 30; // Default 30 minutes
        
        $currentTime = $openingTime->copy();
        
        while ($currentTime->lt($closingTime)) {
            $endTime = $currentTime->copy()->addMinutes($interval);
            
            // Don't create a slot that goes past closing time
            if ($endTime->gt($closingTime)) {
                break;
            }
            
            $slots[] = [
                'start_time' => $currentTime->format('H:i'),
                'end_time' => $endTime->format('H:i'),
                'display' => $currentTime->format('g:i A'),
                'value' => $currentTime->format('H:i'),
            ];
            
            $currentTime->addMinutes($interval);
        }
        
        return response()->json([
            'success' => true,
            'time_slots' => $slots,
        ]);
    });
    Route::post('/reservations', [ReservationController::class, 'store']);
    Route::post('/verify-otp', [ReservationController::class, 'verifyOtp']);
    Route::post('/resend-otp', [ReservationController::class, 'resendOtp']);
    Route::get('/reservation-status', [ReservationController::class, 'checkStatus']);
});

