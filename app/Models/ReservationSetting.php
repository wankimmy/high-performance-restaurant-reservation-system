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
    ];

    protected $casts = [
        'date' => 'date',
        'is_open' => 'boolean',
        'opening_time' => 'datetime',
        'closing_time' => 'datetime',
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
        // Also clear the closed dates cache when a date is updated
        Cache::forget("closed_dates_all");
    }
}

