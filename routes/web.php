<?php

use App\Http\Controllers\Admin\AdminReservationController;
use App\Http\Controllers\Admin\MonitoringController;
use App\Http\Controllers\Admin\TableController;
use App\Http\Controllers\ProfileController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Public routes
Route::get('/', function () {
    return view('booking.index');
})->name('home');

Route::get('/verify-otp', function () {
    return view('booking.verify-otp');
})->name('booking.verify-otp');

Route::get('/queue', function () {
    return view('booking.queue');
})->name('booking.queue');

Route::get('/reservation/result', function (Request $request) {
    $sessionId = $request->query('session_id');
    $status = $request->query('status', 'failed');
    $message = $request->query('message', '');
    
    $reservation = null;
    if ($sessionId && $status === 'confirmed') {
        $otp = \App\Models\Otp::where('session_id', $sessionId)->first();
        if ($otp && $otp->reservation_id) {
            $reservation = \App\Models\Reservation::with('table')->find($otp->reservation_id);
        }
    }
    
    return view('booking.result', [
        'status' => $status,
        'message' => $message,
        'reservation' => $reservation,
    ]);
})->name('booking.result');

// Authentication routes (Breeze)
require __DIR__.'/auth.php';

// Admin routes (protected by authentication)
Route::middleware(['auth', 'verified'])->prefix('admin')->name('admin.')->group(function () {
    // Dashboard
    Route::get('/dashboard', function () {
        return view('admin.dashboard');
    })->name('dashboard');
    
    // Reservations
    Route::get('/reservations', [AdminReservationController::class, 'index'])->name('reservations.index');
    Route::post('/reservations/{id}/cancel', [AdminReservationController::class, 'cancel'])->name('reservations.cancel');
    Route::post('/reservations/{id}/mark-arrived', [AdminReservationController::class, 'markAsArrived'])->name('reservations.mark-arrived');
    
    // Settings
    Route::get('/settings', [AdminReservationController::class, 'getSettings'])->name('settings.index');
    Route::post('/settings/toggle', [AdminReservationController::class, 'toggleDateStatus'])->name('settings.toggle');
    
    // Tables
    Route::resource('tables', TableController::class)->except(['show']);
    Route::post('/tables/{table}/toggle-availability', [TableController::class, 'toggleAvailability'])->name('tables.toggle');
    
    // Monitoring
    Route::get('/monitoring', [MonitoringController::class, 'index'])->name('monitoring.index');
    Route::get('/monitoring/metrics', [MonitoringController::class, 'getMetrics'])->name('monitoring.metrics');
});

// Profile routes (Breeze)
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});
