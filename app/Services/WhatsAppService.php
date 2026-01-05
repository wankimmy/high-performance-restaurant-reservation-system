<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    /**
     * Send OTP via WhatsApp
     * 
     * Note: This is a placeholder implementation. You'll need to integrate with
     * a WhatsApp Business API provider like Twilio, WhatsApp Business API, or similar.
     * 
     * For development/testing, you can use:
     * - Twilio WhatsApp API
     * - WhatsApp Business API (Meta)
     * - Other third-party services
     */
    public function sendOtp(string $phoneNumber, string $otpCode): bool
    {
        try {
            // Format phone number (remove + and spaces, ensure it starts with country code)
            $formattedPhone = $this->formatPhoneNumber($phoneNumber);
            
            // Message content
            $message = "Your reservation OTP code is: {$otpCode}\n\nThis code will expire in 10 minutes.\n\nPlease do not share this code with anyone.";

            // TODO: Replace with actual WhatsApp API integration
            // Example with Twilio WhatsApp API:
            /*
            $response = Http::withBasicAuth(
                config('services.twilio.account_sid'),
                config('services.twilio.auth_token')
            )->post("https://api.twilio.com/2010-04-01/Accounts/{account_sid}/Messages.json", [
                'From' => 'whatsapp:' . config('services.twilio.whatsapp_from'),
                'To' => 'whatsapp:' . $formattedPhone,
                'Body' => $message,
            ]);
            */

            // For now, log the OTP (remove in production)
            Log::info('WhatsApp OTP sent', [
                'phone' => $formattedPhone,
                'otp' => $otpCode,
                'message' => $message,
            ]);

            // In development, you can use a service like:
            // - https://www.twilio.com/whatsapp
            // - https://developers.facebook.com/docs/whatsapp
            // - https://www.messagebird.com/en/whatsapp-api
            
            // For testing purposes, return true
            // In production, check the API response and return accordingly
            return true;

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
            $formattedPhone = $this->formatPhoneNumber($phoneNumber);
            
            $message = "✅ Reservation Confirmed!\n\n";
            $message .= "Reservation ID: #{$reservationDetails['id']}\n";
            $message .= "Name: {$reservationDetails['customer_name']}\n";
            $message .= "Table: {$reservationDetails['table_name']}\n";
            $message .= "Date: {$reservationDetails['reservation_date']}\n";
            $message .= "Time: {$reservationDetails['reservation_time']}\n";
            $message .= "Guests: {$reservationDetails['pax']}\n\n";
            $message .= "Thank you for your reservation!";

            // TODO: Implement actual WhatsApp API call
            Log::info('WhatsApp reservation confirmation sent', [
                'phone' => $formattedPhone,
                'reservation_id' => $reservationDetails['id'],
            ]);

            return true;
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
            $formattedPhone = $this->formatPhoneNumber($phoneNumber);
            
            $message = "🏨 Arrival Verification\n\n";
            $message .= "Hello {$reservationDetails['customer_name']},\n\n";
            $message .= "Please verify your arrival with this OTP code: {$otpCode}\n\n";
            $message .= "Reservation Details:\n";
            $message .= "Table: {$reservationDetails['table_name']}\n";
            $message .= "Date: {$reservationDetails['reservation_date']}\n";
            $message .= "Time: {$reservationDetails['reservation_time']}\n\n";
            $message .= "This code will expire in 10 minutes.";

            // TODO: Implement actual WhatsApp API call
            Log::info('WhatsApp arrival OTP sent', [
                'phone' => $formattedPhone,
                'otp' => $otpCode,
                'reservation_id' => $reservationDetails['id'],
            ]);

            return true;
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
