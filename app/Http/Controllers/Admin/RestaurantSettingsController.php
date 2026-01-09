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
        $openingTime = trim($request->input('opening_time', ''));
        $closingTime = trim($request->input('closing_time', ''));
        
        $validator = Validator::make([
            'opening_time' => $openingTime,
            'closing_time' => $closingTime,
            'deposit_per_pax' => $request->input('deposit_per_pax'),
            'time_slot_interval' => $request->input('time_slot_interval'),
        ], [
            'opening_time' => [
                'required',
                function ($attribute, $value, $fail) {
                    $value = trim($value);
                    if (empty($value)) {
                        $fail('The opening time field is required.');
                        return;
                    }
                    if (!preg_match('/^([0-1][0-9]|2[0-3]):[0-5][0-9]$/', $value)) {
                        $fail('The opening time must be in HH:MM format (e.g., 09:00).');
                    }
                },
            ],
            'closing_time' => [
                'required',
                function ($attribute, $value, $fail) {
                    $value = trim($value);
                    if (empty($value)) {
                        $fail('The closing time field is required.');
                        return;
                    }
                    if (!preg_match('/^([0-1][0-9]|2[0-3]):[0-5][0-9]$/', $value)) {
                        $fail('The closing time must be in HH:MM format (e.g., 22:00).');
                    }
                },
                function ($attribute, $value, $fail) use ($openingTime) {
                    $value = trim($value);
                    $opening = trim($openingTime);
                    
                    if ($opening && $value && preg_match('/^([0-1][0-9]|2[0-3]):[0-5][0-9]$/', $opening) && preg_match('/^([0-1][0-9]|2[0-3]):[0-5][0-9]$/', $value)) {
                        $openingParts = explode(':', $opening);
                        $closingParts = explode(':', $value);
                        
                        if (count($openingParts) !== 2 || count($closingParts) !== 2) {
                            return;
                        }
                        
                        $openingMinutes = (int)$openingParts[0] * 60 + (int)$openingParts[1];
                        $closingMinutes = (int)$closingParts[0] * 60 + (int)$closingParts[1];
                        
                        if ($closingMinutes <= $openingMinutes) {
                            $closingMinutes += 24 * 60;
                        }
                        
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

        $data = $validator->validated();
        $data['opening_time'] = trim($data['opening_time']);
        $data['closing_time'] = trim($data['closing_time']);
        
        $settings = RestaurantSetting::firstOrCreate([], $data);
        $settings->update($data);
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
