<?php

use App\Http\Controllers\Admin\AdminReservationController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\RestaurantSettingsController;
use App\Http\Controllers\Admin\TableController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::get('/', [BookingController::class, 'index'])->name('home');
Route::get('/verify-otp', [BookingController::class, 'verifyOtp'])->name('booking.verify-otp');
Route::get('/queue', [BookingController::class, 'queue'])->name('booking.queue');
Route::get('/reservation/result', [BookingController::class, 'result'])->name('booking.result');

// Authentication routes (Breeze)
require __DIR__.'/auth.php';

// Admin routes (protected by authentication)
Route::middleware(['auth', 'verified'])->prefix('admin')->name('admin.')->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // Reservations
    Route::get('/reservations', [AdminReservationController::class, 'index'])->name('reservations.index');
    Route::post('/reservations/{id}/cancel', [AdminReservationController::class, 'cancel'])->name('reservations.cancel');
    Route::post('/reservations/{id}/request-arrival-verification', [AdminReservationController::class, 'requestArrivalVerification'])->name('reservations.request-arrival-verification');
    Route::post('/reservations/{id}/verify-arrival-otp', [AdminReservationController::class, 'verifyArrivalOtp'])->name('reservations.verify-arrival-otp');
    
    // Restaurant Settings (includes date settings)
    Route::get('/restaurant-settings', [RestaurantSettingsController::class, 'index'])->name('restaurant-settings.index');
    Route::post('/restaurant-settings/update', [RestaurantSettingsController::class, 'update'])->name('restaurant-settings.update');
    Route::get('/restaurant-settings/get', [RestaurantSettingsController::class, 'getSettings'])->name('restaurant-settings.get');
    Route::post('/restaurant-settings/toggle-date', [RestaurantSettingsController::class, 'toggleDateStatus'])->name('restaurant-settings.toggle-date');
    
    // WhatsApp Settings
    Route::get('/whatsapp-settings', [\App\Http\Controllers\Admin\WhatsAppSettingsController::class, 'index'])->name('whatsapp-settings.index');
    Route::get('/whatsapp-settings/status', [\App\Http\Controllers\Admin\WhatsAppSettingsController::class, 'getStatus'])->name('whatsapp-settings.status');
    Route::get('/whatsapp-settings/qr', [\App\Http\Controllers\Admin\WhatsAppSettingsController::class, 'getQrCode'])->name('whatsapp-settings.qr');
    Route::post('/whatsapp-settings/connect', [\App\Http\Controllers\Admin\WhatsAppSettingsController::class, 'connect'])->name('whatsapp-settings.connect');
    Route::post('/whatsapp-settings/disconnect', [\App\Http\Controllers\Admin\WhatsAppSettingsController::class, 'disconnect'])->name('whatsapp-settings.disconnect');
    Route::post('/whatsapp-settings/update', [\App\Http\Controllers\Admin\WhatsAppSettingsController::class, 'update'])->name('whatsapp-settings.update');
    
    // Tables
    Route::resource('tables', TableController::class)->except(['show']);
    Route::post('/tables/{table}/toggle-availability', [TableController::class, 'toggleAvailability'])->name('tables.toggle');
    
});

// Profile routes (Breeze)
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});
