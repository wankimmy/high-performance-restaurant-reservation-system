<?php

namespace App\Jobs;

use App\Models\Reservation;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ConfirmReservation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 30;

    public function __construct(
        public int $reservationId,
        public string $sessionId
    ) {
        $this->onQueue('reservations');
    }

    public function handle(NotificationService $notificationService): void
    {
        try {
            $reservation = Reservation::find($this->reservationId);
            
            if (!$reservation) {
                Cache::put("reservation_status_{$this->sessionId}", [
                    'status' => 'failed',
                    'message' => 'Reservation not found',
                ], 600);
                return;
            }

            // Update reservation status to confirmed
            $reservation->update([
                'status' => 'confirmed',
                'otp_verified' => true,
            ]);

            // Clear availability cache (all pax-specific keys)
            $cacheKeyBase = "available_tables_{$reservation->reservation_date}_{$reservation->reservation_time}";
            Cache::forget($cacheKeyBase);
            // Clear pax-specific cache keys (common pax values 1-20)
            for ($pax = 1; $pax <= 20; $pax++) {
                Cache::forget("{$cacheKeyBase}_{$pax}");
            }

            // Send confirmation notifications
            $notificationService->sendReservationConfirmation($reservation);

            // Update status in cache
            Cache::put("reservation_status_{$this->sessionId}", [
                'status' => 'confirmed',
                'reservation_id' => $reservation->id,
                'message' => 'Reservation confirmed successfully',
            ], 600);

            // Update queue metrics
            $processedToday = Cache::get('queue_processed_today', 0);
            Cache::put('queue_processed_today', $processedToday + 1, now()->endOfDay());

            Log::info('Reservation confirmed successfully', [
                'reservation_id' => $reservation->id,
                'session_id' => $this->sessionId,
                'table_id' => $reservation->table_id,
            ]);
        } catch (\Exception $e) {
            Cache::put("reservation_status_{$this->sessionId}", [
                'status' => 'failed',
                'message' => 'Failed to confirm reservation: ' . $e->getMessage(),
            ], 600);
            
            // Update failed metrics
            $failedToday = Cache::get('queue_failed_today', 0);
            Cache::put('queue_failed_today', $failedToday + 1, now()->endOfDay());
            
            Log::error('Failed to confirm reservation', [
                'error' => $e->getMessage(),
                'reservation_id' => $this->reservationId,
                'session_id' => $this->sessionId,
            ]);
            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Cache::put("reservation_status_{$this->sessionId}", [
            'status' => 'failed',
            'message' => 'Reservation confirmation failed permanently',
        ], 600);
        
        // Update failed metrics
        $failedToday = Cache::get('queue_failed_today', 0);
        Cache::put('queue_failed_today', $failedToday + 1, now()->endOfDay());
        
        Log::error('Reservation confirmation job failed permanently', [
            'error' => $exception->getMessage(),
            'reservation_id' => $this->reservationId,
            'session_id' => $this->sessionId,
        ]);
    }
}
