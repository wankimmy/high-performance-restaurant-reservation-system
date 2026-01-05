<?php

use App\Http\Controllers\Api\ReservationController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('/availability', [ReservationController::class, 'checkAvailability']);
    Route::post('/reservations', [ReservationController::class, 'store']);
});

