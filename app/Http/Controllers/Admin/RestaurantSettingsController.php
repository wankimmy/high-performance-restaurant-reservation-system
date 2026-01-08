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
        return view('admin.restaurant-settings.index', compact('settings'));
    }

    /**
     * Update restaurant settings
     */
    public function update(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'opening_time' => 'required|date_format:H:i',
            'closing_time' => 'required|date_format:H:i|after:opening_time',
            'deposit_per_pax' => 'required|numeric|min:0',
            'time_slot_interval' => 'required|integer|min:15|max:120',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        
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
}
