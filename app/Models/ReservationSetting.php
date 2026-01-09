<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class ReservationSetting extends Model
{
    protected $fillable = [
        'date',
        'is_open',
        'opening_time',
        'closing_time',
        'time_slot_interval',
        'deposit_per_pax',
    ];

    protected $casts = [
        'date' => 'date',
        'is_open' => 'boolean',
        'opening_time' => 'string',
        'closing_time' => 'string',
        'time_slot_interval' => 'integer',
        'deposit_per_pax' => 'decimal:2',
    ];

    public static function isDateOpen(string $date): bool
    {
        return Cache::remember("reservation_date_open_{$date}", 3600, function () use ($date) {
            $setting = self::where('date', $date)->first();
            
            if (!$setting) {
                return true; // Default to open if no setting exists
            }
            
            return $setting->is_open;
        });
    }

    public static function clearCache(string $date): void
    {
        Cache::forget("reservation_date_open_{$date}");
        Cache::forget("reservation_date_settings_{$date}");
        // Also clear the closed dates cache when a date is updated
        Cache::forget("closed_dates_all");
    }

    /**
     * Get settings for a specific date (with fallback to global settings)
     */
    public static function getDateSettings(string $date): array
    {
        return Cache::remember("reservation_date_settings_{$date}", 3600, function () use ($date) {
            $dateSetting = self::where('date', $date)->first();
            $globalSettings = RestaurantSetting::getSettings();
            
            return [
                'opening_time' => ($dateSetting && $dateSetting->opening_time) ? $dateSetting->opening_time : $globalSettings->opening_time,
                'closing_time' => ($dateSetting && $dateSetting->closing_time) ? $dateSetting->closing_time : $globalSettings->closing_time,
                'time_slot_interval' => ($dateSetting && $dateSetting->time_slot_interval !== null) ? $dateSetting->time_slot_interval : ($globalSettings->time_slot_interval ?? 30),
                'deposit_per_pax' => ($dateSetting && $dateSetting->deposit_per_pax !== null) ? $dateSetting->deposit_per_pax : ($globalSettings->deposit_per_pax ?? 0.00),
            ];
        });
    }

    /**
     * Calculate deposit for a specific date
     */
    public static function calculateDepositForDate(string $date, int $pax): float
    {
        $settings = self::getDateSettings($date);
        return (float) ($settings['deposit_per_pax'] * $pax);
    }
}

