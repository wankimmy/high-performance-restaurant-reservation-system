<?php

namespace App\Services;

use App\Models\WhatsAppSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    /**
     * Send OTP via WhatsApp using Baileys service
     */
    public function sendOtp(string $phoneNumber, string $otpCode, ?string $customerName = null): bool
    {
        try {
            $settings = WhatsAppSetting::getSettings();
            
            // Check if WhatsApp is enabled and connected
            if (!$settings->is_enabled || !$settings->isConnected()) {
                Log::warning('WhatsApp not enabled or not connected', [
                    'is_enabled' => $settings->is_enabled,
                    'status' => $settings->status,
                ]);
                return false;
            }

            // Format phone number
            $formattedPhone = $this->formatPhoneNumber($phoneNumber);
            
            // Get restaurant name from config
            $restaurantName = config('app.name', 'Restaurant Reservation System');
            
            // Build friendly message
            $message = "🍽️ *{$restaurantName}*\n\n";
            
            if ($customerName) {
                $message .= "Hi {$customerName},\n\n";
            } else {
                $message .= "Hello,\n\n";
            }
            
            $message .= "Thank you for making a reservation with us! 😊\n\n";
            $message .= "Your verification code is:\n";
            $message .= "*{$otpCode}*\n\n";
            $message .= "This code will expire in 10 minutes.\n\n";
            $message .= "Please do not share this code with anyone for security purposes.\n\n";
            $message .= "We look forward to serving you! 🎉";

            // Send via Baileys service
            $response = Http::timeout(10)->post("{$settings->service_url}/api/send-message", [
                'phone' => $formattedPhone,
                'message' => $message,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                if ($data['success'] ?? false) {
                    Log::info('WhatsApp OTP sent successfully', [
                        'phone' => $formattedPhone,
                        'otp' => $otpCode,
                    ]);
                    return true;
                }
            }

            Log::error('Failed to send WhatsApp OTP', [
                'phone' => $formattedPhone,
                'response' => $response->body(),
            ]);
            return false;

        } catch (\Exception $e) {
            Log::error('Failed to send WhatsApp OTP', [
                'phone' => $phoneNumber,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Send reservation confirmation via WhatsApp
     */
    public function sendReservationConfirmation(string $phoneNumber, array $reservationDetails): bool
    {
        try {
            $settings = WhatsAppSetting::getSettings();
            
            if (!$settings->is_enabled || !$settings->isConnected()) {
                Log::warning('WhatsApp not enabled or not connected for confirmation');
                return false;
            }

            $formattedPhone = $this->formatPhoneNumber($phoneNumber);
            
            $message = "✅ Reservation Confirmed!\n\n";
            $message .= "Reservation ID: #{$reservationDetails['id']}\n";
            $message .= "Name: {$reservationDetails['customer_name']}\n";
            $message .= "Table: {$reservationDetails['table_name']}\n";
            $message .= "Date: {$reservationDetails['reservation_date']}\n";
            $message .= "Time: {$reservationDetails['reservation_time']}\n";
            $message .= "Guests: {$reservationDetails['pax']}\n\n";
            $message .= "Thank you for your reservation!";

            $response = Http::timeout(10)->post("{$settings->service_url}/api/send-message", [
                'phone' => $formattedPhone,
                'message' => $message,
            ]);

            if ($response->successful() && ($response->json()['success'] ?? false)) {
                Log::info('WhatsApp reservation confirmation sent', [
                    'phone' => $formattedPhone,
                    'reservation_id' => $reservationDetails['id'],
                ]);
                return true;
            }

            return false;
        } catch (\Exception $e) {
            Log::error('Failed to send WhatsApp confirmation', [
                'phone' => $phoneNumber,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Send arrival verification OTP via WhatsApp
     */
    public function sendArrivalOtp(string $phoneNumber, string $otpCode, array $reservationDetails): bool
    {
        try {
            $settings = WhatsAppSetting::getSettings();
            
            if (!$settings->is_enabled || !$settings->isConnected()) {
                Log::warning('WhatsApp not enabled or not connected for arrival OTP');
                return false;
            }

            $formattedPhone = $this->formatPhoneNumber($phoneNumber);
            
            $message = "🏨 Arrival Verification\n\n";
            $message .= "Hello {$reservationDetails['customer_name']},\n\n";
            $message .= "Please verify your arrival with this OTP code: *{$otpCode}*\n\n";
            $message .= "Reservation Details:\n";
            $message .= "Table: {$reservationDetails['table_name']}\n";
            $message .= "Date: {$reservationDetails['reservation_date']}\n";
            $message .= "Time: {$reservationDetails['reservation_time']}\n\n";
            $message .= "This code will expire in 10 minutes.";

            $response = Http::timeout(10)->post("{$settings->service_url}/api/send-message", [
                'phone' => $formattedPhone,
                'message' => $message,
            ]);

            if ($response->successful() && ($response->json()['success'] ?? false)) {
                Log::info('WhatsApp arrival OTP sent', [
                    'phone' => $formattedPhone,
                    'otp' => $otpCode,
                    'reservation_id' => $reservationDetails['id'],
                ]);
                return true;
            }

            return false;
        } catch (\Exception $e) {
            Log::error('Failed to send WhatsApp arrival OTP', [
                'phone' => $phoneNumber,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Format phone number for WhatsApp
     */
    private function formatPhoneNumber(string $phoneNumber): string
    {
        // Remove all non-numeric characters except +
        $phone = preg_replace('/[^0-9+]/', '', $phoneNumber);
        
        // If doesn't start with +, assume it needs country code
        // You may need to adjust this based on your requirements
        if (!str_starts_with($phone, '+')) {
            // Default to Malaysia country code if not specified
            // Change this based on your default country
            if (!str_starts_with($phone, '60')) {
                $phone = '60' . ltrim($phone, '0');
            }
            $phone = '+' . $phone;
        }
        
        return $phone;
    }
}
