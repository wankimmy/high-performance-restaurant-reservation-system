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
        // Show all date settings (both open and closed dates with custom settings)
        // This includes dates that are closed OR have custom opening/closing times, intervals, or deposits
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
     * Save date settings (close date or set per-date configuration)
     */
    public function saveDateSettings(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'date' => 'required|date|after_or_equal:today',
            'is_open' => 'nullable|boolean',
            'opening_time' => 'nullable|string',
            'closing_time' => 'nullable|string',
            'time_slot_interval' => 'nullable|integer|min:15|max:120',
            'deposit_per_pax' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $date = $data['date'];

        // Validate time format if provided
        if (isset($data['opening_time']) && !empty($data['opening_time'])) {
            if (!preg_match('/^([0-1][0-9]|2[0-3]):[0-5][0-9]$/', $data['opening_time'])) {
                return response()->json([
                    'success' => false,
                    'errors' => ['opening_time' => ['The opening time must be in HH:MM format (e.g., 09:00).']],
                ], 422);
            }
        }

        if (isset($data['closing_time']) && !empty($data['closing_time'])) {
            if (!preg_match('/^([0-1][0-9]|2[0-3]):[0-5][0-9]$/', $data['closing_time'])) {
                return response()->json([
                    'success' => false,
                    'errors' => ['closing_time' => ['The closing time must be in HH:MM format (e.g., 22:00).']],
                ], 422);
            }
        }

        // Validate time range if both are provided
        if (!empty($data['opening_time']) && !empty($data['closing_time'])) {
            $openingParts = explode(':', $data['opening_time']);
            $closingParts = explode(':', $data['closing_time']);
            $openingMinutes = (int)$openingParts[0] * 60 + (int)$openingParts[1];
            $closingMinutes = (int)$closingParts[0] * 60 + (int)$closingParts[1];
            
            if ($closingMinutes <= $openingMinutes) {
                $closingMinutes += 24 * 60;
            }
            
            if (($closingMinutes - $openingMinutes) < 60) {
                return response()->json([
                    'success' => false,
                    'errors' => ['closing_time' => ['The closing time must be at least 1 hour after the opening time.']],
                ], 422);
            }
        }

        // Create or update setting
        $setting = \App\Models\ReservationSetting::firstOrNew(['date' => $date]);
        
        // If is_open is explicitly set, use it; otherwise keep existing value or default to true
        if (isset($data['is_open'])) {
            $setting->is_open = $data['is_open'];
        } elseif (!$setting->exists) {
            $setting->is_open = true; // Default to open for new records
        }
        
        // Set optional fields (only if provided)
        if (isset($data['opening_time'])) {
            $setting->opening_time = !empty($data['opening_time']) ? $data['opening_time'] : null;
        }
        if (isset($data['closing_time'])) {
            $setting->closing_time = !empty($data['closing_time']) ? $data['closing_time'] : null;
        }
        if (isset($data['time_slot_interval'])) {
            $setting->time_slot_interval = !empty($data['time_slot_interval']) ? $data['time_slot_interval'] : null;
        }
        if (isset($data['deposit_per_pax'])) {
            $setting->deposit_per_pax = !empty($data['deposit_per_pax']) ? $data['deposit_per_pax'] : null;
        }
        
        $setting->save();

        // Clear cache for this specific date
        \App\Models\ReservationSetting::clearCache($date);

        return response()->json([
            'success' => true,
            'message' => 'Date settings saved successfully',
            'setting' => $setting,
        ]);
    }

    /**
     * Close a date (create closed date setting)
     */
    public function closeDate(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'date' => 'required|date|after_or_equal:today',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $date = $validator->validated()['date'];

        // Create or update setting to closed
        $setting = \App\Models\ReservationSetting::firstOrNew(['date' => $date]);
        $setting->is_open = false;
        $setting->save();

        // Clear cache for this specific date and the closed dates list
        \App\Models\ReservationSetting::clearCache($date);

        return response()->json([
            'success' => true,
            'message' => 'Date closed successfully',
            'setting' => $setting,
        ]);
    }

    /**
     * Reopen a closed date (delete the closed date setting, making it open by default)
     */
    public function reopenDate(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'date' => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $date = $validator->validated()['date'];

        // Delete the setting - by default, dates are open
        $setting = \App\Models\ReservationSetting::where('date', $date)->first();
        if ($setting) {
            $setting->delete();
        }

        // Clear cache for this specific date and the closed dates list
        \App\Models\ReservationSetting::clearCache($date);

        return response()->json([
            'success' => true,
            'message' => 'Date reopened successfully',
        ]);
    }
}
