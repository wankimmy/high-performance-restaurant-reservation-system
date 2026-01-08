<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class TimeSlot extends Model
{
    protected $fillable = [
        'start_time',
        'end_time',
        'is_available',
        'notes',
    ];

    protected $casts = [
        'start_time' => 'string',
        'end_time' => 'string',
        'is_available' => 'boolean',
    ];

    /**
     * Get all available time slots
     */
    public static function getAvailableSlots(): \Illuminate\Support\Collection
    {
        return Cache::remember('available_time_slots', 3600, function () {
            return self::where('is_available', true)
                ->orderBy('start_time')
                ->get();
        });
    }

    /**
     * Clear time slots cache
     */
    public static function clearCache(): void
    {
        Cache::forget('available_time_slots');
        Cache::forget('generated_time_slots');
    }

    /**
     * Generate time slots based on restaurant settings
     */
    public static function generateFromSettings(): array
    {
        return Cache::remember('generated_time_slots', 3600, function () {
            $settings = \App\Models\RestaurantSetting::getSettings();
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
            
            return $slots;
        });
    }

    /**
     * Get time slots (either from database or generated from settings)
     */
    public static function getTimeSlots(): array
    {
        // Check if there are custom time slots in database
        $customSlots = self::getAvailableSlots();
        
        if ($customSlots->isNotEmpty()) {
            // Use custom time slots from database
            return $customSlots->map(function ($slot) {
                $startTime = \Carbon\Carbon::parse($slot->start_time);
                return [
                    'start_time' => $slot->start_time,
                    'end_time' => $slot->end_time,
                    'display' => $startTime->format('g:i A'),
                    'value' => $slot->start_time,
                    'is_custom' => true,
                ];
            })->toArray();
        }
        
        // Otherwise, generate from settings
        return self::generateFromSettings();
    }
}
