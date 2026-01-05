<?php

use App\Http\Controllers\Admin\AdminReservationController;
use App\Http\Controllers\Auth\LoginController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::get('/', function () {
    return view('booking.index');
})->name('home');

// Authentication routes
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Admin routes (protected by authentication)
Route::prefix('admin')->middleware(['auth'])->group(function () {
    Route::get('/reservations', [AdminReservationController::class, 'index'])->name('admin.reservations.index');
    Route::post('/reservations/{id}/cancel', [AdminReservationController::class, 'cancel'])->name('admin.reservations.cancel');
    Route::get('/settings', [AdminReservationController::class, 'getSettings'])->name('admin.settings.index');
    Route::post('/settings/toggle', [AdminReservationController::class, 'toggleDateStatus'])->name('admin.settings.toggle');
    Route::get('/monitoring', [\App\Http\Controllers\Admin\MonitoringController::class, 'index'])->name('admin.monitoring.index');
    Route::get('/monitoring/metrics', [\App\Http\Controllers\Admin\MonitoringController::class, 'getMetrics'])->name('admin.monitoring.metrics');
});

