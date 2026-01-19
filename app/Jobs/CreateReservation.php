<?php

namespace App\Jobs;

use App\Models\Reservation;
use App\Models\ReservationSetting;
use App\Models\Table;
use App\Services\OtpService;
use App\Services\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CreateReservation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 30;

    public function __construct(
        public array $data,
        public string $sessionId,
        public ?string $ipAddress = null,
        public ?string $userAgent = null
    ) {
        $this->onQueue('reservations');
    }

    public function handle(OtpService $otpService, WhatsAppService $whatsAppService): void
    {
        try {
            // Final validation before creating reservation
            $table = Table::findOrFail($this->data['table_id']);

            // Check if date is open
            if (!ReservationSetting::isDateOpen($this->data['reservation_date'])) {
                Cache::put("reservation_status_{$this->sessionId}", [
                    'status' => 'failed',
                    'message' => 'Reservations are closed for this date',
                ], 600);
                return;
            }

            // Check if table already has a reservation at this time
            if ($table->hasReservationAt($this->data['reservation_date'], $this->data['reservation_time'])) {
                Cache::put("reservation_status_{$this->sessionId}", [
                    'status' => 'failed',
                    'message' => 'This table is already reserved at the selected time',
                ], 600);
                Log::warning('Reservation conflict detected', [
                    'table_id' => $this->data['table_id'],
                    'date' => $this->data['reservation_date'],
                    'time' => $this->data['reservation_time'],
                ]);
                return;
            }

            // Check table capacity
            if ($this->data['pax'] > $table->capacity) {
                Cache::put("reservation_status_{$this->sessionId}", [
                    'status' => 'failed',
                    'message' => "This table can only accommodate {$table->capacity} people",
                ], 600);
                return;
            }

            // Calculate deposit amount
            $depositAmount = ReservationSetting::calculateDepositForDate($this->data['reservation_date'], $this->data['pax']);

            // Create pending reservation
            $reservation = Reservation::create([
                'table_id' => $this->data['table_id'],
                'customer_name' => $this->data['customer_name'],
                'customer_email' => $this->data['customer_email'],
                'customer_phone' => $this->data['customer_phone'],
                'pax' => $this->data['pax'],
                'deposit_amount' => $depositAmount,
                'reservation_date' => $this->data['reservation_date'],
                'reservation_time' => $this->data['reservation_time'],
                'notes' => $this->data['notes'] ?? null,
                'status' => 'pending',
                'ip_address' => $this->ipAddress,
                'user_agent' => $this->userAgent,
            ]);

            // Generate OTP
            $otpData = $otpService->generateOtp($this->data['customer_phone'], $reservation->id);
            
            // Update reservation with OTP session ID
            $reservation->update(['otp_session_id' => $otpData['session_id']]);

            // Get OTP code from database
            $otp = $otpService->getOtpBySession($otpData['session_id']);
            
            // Send OTP via WhatsApp (skip for k6 load tests)
            $isK6Test = $this->userAgent && (
                str_contains(strtolower($this->userAgent), 'k6') ||
                str_contains(strtolower($this->userAgent), 'k6-load-test') ||
                str_contains(strtolower($this->userAgent), 'k6-stress-test') ||
                str_contains(strtolower($this->userAgent), 'k6-booking-flow')
            );
            
            if ($otp && !$isK6Test) {
                $whatsAppService->sendOtp($this->data['customer_phone'], $otp->otp_code, $this->data['customer_name']);
            }

            // Store reservation info in cache for status tracking
            // Use both session IDs for tracking
            Cache::put("reservation_status_{$this->sessionId}", [
                'status' => 'pending',
                'reservation_id' => $reservation->id,
                'otp_session_id' => $otpData['session_id'],
                'message' => 'OTP sent to your WhatsApp number',
            ], 600);
            
            // Also store status with OTP session ID (for OTP verification flow)
            Cache::put("reservation_status_{$otpData['session_id']}", [
                'status' => 'pending',
                'reservation_id' => $reservation->id,
                'otp_session_id' => $otpData['session_id'],
                'message' => 'OTP sent to your WhatsApp number',
            ], 600);

            Log::info('Reservation created successfully', [
                'reservation_id' => $reservation->id,
                'session_id' => $this->sessionId,
                'table_id' => $reservation->table_id,
            ]);
        } catch (\Exception $e) {
            Cache::put("reservation_status_{$this->sessionId}", [
                'status' => 'failed',
                'message' => 'Failed to create reservation: ' . $e->getMessage(),
            ], 600);
            
            Log::error('Failed to create reservation', [
                'error' => $e->getMessage(),
                'data' => $this->data,
                'session_id' => $this->sessionId,
            ]);
            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Cache::put("reservation_status_{$this->sessionId}", [
            'status' => 'failed',
            'message' => 'Reservation creation failed permanently',
        ], 600);
        
        Log::error('Reservation creation job failed permanently', [
            'error' => $exception->getMessage(),
            'data' => $this->data,
            'session_id' => $this->sessionId,
        ]);
    }
}
