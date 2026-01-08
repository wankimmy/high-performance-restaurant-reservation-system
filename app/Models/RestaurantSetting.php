<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class RestaurantSetting extends Model
{
    protected $fillable = [
        'opening_time',
        'closing_time',
        'deposit_per_pax',
        'time_slot_interval',
    ];

    protected $casts = [
        'opening_time' => 'string',
        'closing_time' => 'string',
        'deposit_per_pax' => 'decimal:2',
        'time_slot_interval' => 'integer',
    ];

    /**
     * Get the current restaurant settings (singleton pattern)
     */
    public static function getSettings(): self
    {
        return Cache::remember('restaurant_settings', 3600, function () {
            return self::first() ?? self::create([
                'opening_time' => '09:00:00',
                'closing_time' => '22:00:00',
                'deposit_per_pax' => 0.00,
                'time_slot_interval' => 30,
            ]);
        });
    }

    /**
     * Clear settings cache
     */
    public static function clearCache(): void
    {
        Cache::forget('restaurant_settings');
    }

    /**
     * Calculate deposit amount based on number of guests
     */
    public static function calculateDeposit(int $pax): float
    {
        $settings = self::getSettings();
        return (float) ($settings->deposit_per_pax * $pax);
    }
}
