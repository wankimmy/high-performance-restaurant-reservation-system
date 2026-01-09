<?php

use App\Http\Controllers\Api\ReservationController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('/availability', [ReservationController::class, 'checkAvailability']);
    Route::get('/closed-dates', [ReservationController::class, 'getClosedDates']);
    Route::get('/restaurant-settings', [ReservationController::class, 'getRestaurantSettings']);
    Route::get('/time-slots', [ReservationController::class, 'getTimeSlots']);
    Route::post('/reservations', [ReservationController::class, 'store']);
    Route::post('/verify-otp', [ReservationController::class, 'verifyOtp']);
    Route::post('/resend-otp', [ReservationController::class, 'resendOtp']);
    Route::get('/reservation-status', [ReservationController::class, 'checkStatus']);
});

