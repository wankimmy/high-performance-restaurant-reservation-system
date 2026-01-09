<?php

namespace App\Services;

use App\Models\Otp;
use Illuminate\Support\Str;

class OtpService
{
    /**
     * Generate and store OTP for a phone number
     */
    public function generateOtp(string $phoneNumber, ?int $reservationId = null): array
    {
        // Invalidate any existing OTPs for this phone number
        Otp::where('phone_number', $phoneNumber)
            ->where('is_verified', false)
            ->update(['is_verified' => true]);

        // Generate 6-digit OTP
        $otpCode = str_pad((string) rand(100000, 999999), 6, '0', STR_PAD_LEFT);
        
        // Create session ID
        $sessionId = Str::uuid()->toString();

        // Create OTP record (expires in 10 minutes)
        $otp = Otp::create([
            'phone_number' => $phoneNumber,
            'otp_code' => $otpCode,
            'session_id' => $sessionId,
            'reservation_id' => $reservationId,
            'expires_at' => now()->addMinutes(10),
        ]);

        return [
            'session_id' => $sessionId,
            'expires_at' => $otp->expires_at,
        ];
    }

    /**
     * Verify OTP code
     */
    public function verifyOtp(string $sessionId, string $otpCode): array
    {
        $otp = Otp::where('session_id', $sessionId)
            ->where('otp_code', $otpCode)
            ->first();

        if (!$otp) {
            return [
                'success' => false,
                'message' => 'Invalid OTP code',
            ];
        }

        if ($otp->isExpired()) {
            return [
                'success' => false,
                'message' => 'OTP has expired. Please request a new one.',
            ];
        }

        if ($otp->is_verified) {
            return [
                'success' => false,
                'message' => 'OTP has already been used',
            ];
        }

        if ($otp->attempts >= 5) {
            return [
                'success' => false,
                'message' => 'Too many failed attempts. Please request a new OTP.',
            ];
        }

        // Increment attempts
        $otp->increment('attempts');

        // Verify OTP
        if ($otp->otp_code === $otpCode) {
            $otp->update(['is_verified' => true]);

            return [
                'success' => true,
                'message' => 'OTP verified successfully',
                'reservation_id' => $otp->reservation_id,
            ];
        }

        return [
            'success' => false,
            'message' => 'Invalid OTP code',
        ];
    }

    /**
     * Get OTP by session ID
     */
    public function getOtpBySession(string $sessionId): ?Otp
    {
        return Otp::where('session_id', $sessionId)->first();
    }
}

