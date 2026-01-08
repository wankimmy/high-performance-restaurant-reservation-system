<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WhatsAppSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class WhatsAppSettingsController extends Controller
{
    /**
     * Display WhatsApp settings page
     */
    public function index()
    {
        $settings = WhatsAppSetting::getSettings();
        return view('admin.whatsapp-settings.index', compact('settings'));
    }

    /**
     * Get WhatsApp status from service
     */
    public function getStatus(): JsonResponse
    {
        try {
            $settings = WhatsAppSetting::getSettings();
            
            if (!$settings->service_url) {
                return response()->json([
                    'success' => false,
                    'error' => 'WhatsApp service URL not configured',
                ], 400);
            }

            $response = Http::timeout(5)->get("{$settings->service_url}/api/status");

            if ($response->successful()) {
                $data = $response->json();
                
                // Update local status
                $qrCode = null;
                if ($data['hasQr'] ?? false) {
                    $qrResponse = Http::timeout(5)->get("{$settings->service_url}/api/qr");
                    if ($qrResponse->successful()) {
                        $qrData = $qrResponse->json();
                        $qrCode = $qrData['qr'] ?? null;
                    }
                }
                
                $settings->update([
                    'status' => $data['status'] ?? 'disconnected',
                    'qr_code' => $qrCode,
                ]);

                WhatsAppSetting::clearCache();

                return response()->json([
                    'success' => true,
                    'status' => $data['status'] ?? 'disconnected',
                    'connected' => $data['connected'] ?? false,
                    'hasQr' => $data['hasQr'] ?? false,
                ]);
            }

            return response()->json([
                'success' => false,
                'error' => 'Failed to connect to WhatsApp service',
            ], 500);
        } catch (\Exception $e) {
            Log::error('Failed to get WhatsApp status', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get QR code from service (API endpoint)
     */
    public function getQrCode(): JsonResponse
    {
        try {
            $settings = WhatsAppSetting::getSettings();
            
            if (!$settings->service_url) {
                return response()->json([
                    'success' => false,
                    'error' => 'WhatsApp service URL not configured',
                ], 400);
            }

            $response = Http::timeout(5)->get("{$settings->service_url}/api/qr");

            if ($response->successful()) {
                $data = $response->json();
                
                return response()->json([
                    'success' => true,
                    'qr' => $data['qr'] ?? null,
                ]);
            }

            return response()->json([
                'success' => false,
                'error' => 'Failed to get QR code',
            ], 500);
        } catch (\Exception $e) {
            Log::error('Failed to get QR code', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Connect to WhatsApp
     */
    public function connect(): JsonResponse
    {
        try {
            $settings = WhatsAppSetting::getSettings();
            
            if (!$settings->service_url) {
                return response()->json([
                    'success' => false,
                    'error' => 'WhatsApp service URL not configured',
                ], 400);
            }

            $response = Http::timeout(10)->post("{$settings->service_url}/api/connect");

            if ($response->successful()) {
                $data = $response->json();
                
                $settings->update([
                    'status' => $data['status'] ?? 'connecting',
                ]);

                WhatsAppSetting::clearCache();

                return response()->json([
                    'success' => true,
                    'message' => $data['message'] ?? 'Connection initiated',
                    'status' => $data['status'] ?? 'connecting',
                ]);
            }

            return response()->json([
                'success' => false,
                'error' => 'Failed to connect to WhatsApp service',
            ], 500);
        } catch (\Exception $e) {
            Log::error('Failed to connect WhatsApp', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Disconnect from WhatsApp
     */
    public function disconnect(): JsonResponse
    {
        try {
            $settings = WhatsAppSetting::getSettings();
            
            if (!$settings->service_url) {
                return response()->json([
                    'success' => false,
                    'error' => 'WhatsApp service URL not configured',
                ], 400);
            }

            $response = Http::timeout(5)->post("{$settings->service_url}/api/disconnect");

            if ($response->successful()) {
                $settings->update([
                    'status' => 'disconnected',
                    'qr_code' => null,
                ]);

                WhatsAppSetting::clearCache();

                return response()->json([
                    'success' => true,
                    'message' => 'Disconnected successfully',
                ]);
            }

            return response()->json([
                'success' => false,
                'error' => 'Failed to disconnect from WhatsApp service',
            ], 500);
        } catch (\Exception $e) {
            Log::error('Failed to disconnect WhatsApp', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update WhatsApp settings
     */
    public function update(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'is_enabled' => 'sometimes|boolean',
            'service_url' => 'sometimes|url',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $settings = WhatsAppSetting::getSettings();
        $settings->update($validator->validated());

        WhatsAppSetting::clearCache();

        return response()->json([
            'success' => true,
            'message' => 'WhatsApp settings updated successfully',
            'settings' => $settings,
        ]);
    }
}
