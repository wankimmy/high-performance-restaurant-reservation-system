<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RestaurantSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RestaurantSettingsController extends Controller
{
    /**
     * Display restaurant settings page
     */
    public function index()
    {
        $settings = RestaurantSetting::getSettings();
        $dateSettings = \App\Models\ReservationSetting::where('date', '>=', now()->format('Y-m-d'))
            ->orderBy('date')
            ->get();
        return view('admin.restaurant-settings.index', compact('settings', 'dateSettings'));
    }

    /**
     * Update restaurant settings
     */
    public function update(Request $request): JsonResponse
    {
        // Get raw input for debugging
        $rawData = $request->all();
        
        // Normalize time values - trim whitespace and ensure proper format
        $openingTime = trim($request->input('opening_time', ''));
        $closingTime = trim($request->input('closing_time', ''));
        
        // Custom validation for time format (more flexible)
        $validator = Validator::make([
            'opening_time' => $openingTime,
            'closing_time' => $closingTime,
            'deposit_per_pax' => $request->input('deposit_per_pax'),
            'time_slot_interval' => $request->input('time_slot_interval'),
        ], [
            'opening_time' => [
                'required',
                function ($attribute, $value, $fail) {
                    if (empty($value) || trim($value) === '') {
                        $fail('The opening time field is required.');
                        return;
                    }
                    $value = trim($value);
                    // Accept HH:MM format (HTML5 time input format)
                    // Pattern: HH:MM where HH is 00-23 and MM is 00-59
                    if (!preg_match('/^([0-1][0-9]|2[0-3]):[0-5][0-9]$/', $value)) {
                        $fail('The opening time must be in HH:MM format (e.g., 09:00). Received: "' . $value . '"');
                    }
                },
            ],
            'closing_time' => [
                'required',
                function ($attribute, $value, $fail) {
                    if (empty($value) || trim($value) === '') {
                        $fail('The closing time field is required.');
                        return;
                    }
                    $value = trim($value);
                    // Accept HH:MM format (HTML5 time input format)
                    if (!preg_match('/^([0-1][0-9]|2[0-3]):[0-5][0-9]$/', $value)) {
                        $fail('The closing time must be in HH:MM format (e.g., 22:00). Received: "' . $value . '"');
                    }
                },
                function ($attribute, $value, $fail) use ($openingTime) {
                    $value = trim($value);
                    $opening = trim($openingTime);
                    
                    if ($opening && $value && preg_match('/^([0-1][0-9]|2[0-3]):[0-5][0-9]$/', $opening) && preg_match('/^([0-1][0-9]|2[0-3]):[0-5][0-9]$/', $value)) {
                        // Convert to minutes for comparison
                        $openingParts = explode(':', $opening);
                        $closingParts = explode(':', $value);
                        
                        if (count($openingParts) !== 2 || count($closingParts) !== 2) {
                            $fail('Invalid time format.');
                            return;
                        }
                        
                        $openingMinutes = (int)$openingParts[0] * 60 + (int)$openingParts[1];
                        $closingMinutes = (int)$closingParts[0] * 60 + (int)$closingParts[1];
                        
                        // Allow closing time to be after midnight (e.g., 22:00 to 02:00)
                        // If closing is less than or equal to opening, assume it's the next day
                        if ($closingMinutes <= $openingMinutes) {
                            $closingMinutes += 24 * 60; // Add 24 hours
                        }
                        
                        // Ensure at least 1 hour difference
                        if (($closingMinutes - $openingMinutes) < 60) {
                            $fail('The closing time must be at least 1 hour after the opening time.');
                        }
                    }
                },
            ],
            'deposit_per_pax' => 'required|numeric|min:0',
            'time_slot_interval' => 'required|integer|min:15|max:120',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed. Please check your input.',
                'errors' => $validator->errors(),
            ], 422);
        }

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed. Please check your input.',
                'errors' => $validator->errors(),
                'debug' => [
                    'raw_opening_time' => $request->input('opening_time'),
                    'raw_closing_time' => $request->input('closing_time'),
                    'normalized_opening_time' => $openingTime,
                    'normalized_closing_time' => $closingTime,
                ],
            ], 422);
        }

        $data = $validator->validated();
        
        // Ensure time values are properly formatted
        $data['opening_time'] = trim($data['opening_time']);
        $data['closing_time'] = trim($data['closing_time']);
        
        $settings = RestaurantSetting::first();
        
        if (!$settings) {
            $settings = RestaurantSetting::create($data);
        } else {
            $settings->update($data);
        }

        // Clear cache
        RestaurantSetting::clearCache();

        return response()->json([
            'success' => true,
            'message' => 'Restaurant settings updated successfully',
            'settings' => $settings,
        ]);
    }

    /**
     * Get restaurant settings (API)
     */
    public function getSettings(): JsonResponse
    {
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
    }

    /**
     * Toggle date status (moved from AdminReservationController)
     */
    public function toggleDateStatus(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'date' => 'required|date',
            'is_open' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        $setting = \App\Models\ReservationSetting::firstOrNew(['date' => $data['date']]);
        $setting->is_open = $data['is_open'];
        $setting->save();

        // Clear cache for this specific date and the closed dates list
        \App\Models\ReservationSetting::clearCache($data['date']);

        return response()->json([
            'success' => true,
            'message' => 'Reservation date status updated',
            'setting' => $setting,
        ]);
    }
}
