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
        $slots = \App\Models\TimeSlot::getTimeSlots();
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

