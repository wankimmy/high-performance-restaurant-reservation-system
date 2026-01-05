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

class ProcessReservation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 30;

    public function __construct(
        public array $data,
        public ?string $ipAddress = null,
        public ?string $userAgent = null
    ) {
        $this->onQueue('reservations');
    }

    public function handle(): void
    {
        try {
            // Check if reservation already exists (from OTP flow)
            $reservation = null;
            if (isset($this->data['id'])) {
                $reservation = Reservation::find($this->data['id']);
            }

            if (!$reservation) {
                // Final validation before creating reservation
                $table = \App\Models\Table::findOrFail($this->data['table_id']);

                if ($table->hasReservationAt($this->data['reservation_date'], $this->data['reservation_time'])) {
                    Log::warning('Reservation conflict detected', [
                        'table_id' => $this->data['table_id'],
                        'date' => $this->data['reservation_date'],
                        'time' => $this->data['reservation_time'],
                    ]);
                    return;
                }

                $reservation = Reservation::create([
                    'table_id' => $this->data['table_id'],
                    'customer_name' => $this->data['customer_name'],
                    'customer_email' => $this->data['customer_email'],
                    'customer_phone' => $this->data['customer_phone'],
                    'pax' => $this->data['pax'],
                    'reservation_date' => $this->data['reservation_date'],
                    'reservation_time' => $this->data['reservation_time'],
                    'notes' => $this->data['notes'] ?? null,
                    'status' => 'confirmed',
                    'ip_address' => $this->ipAddress,
                    'user_agent' => $this->userAgent,
                ]);
            } else {
                // Ensure reservation is confirmed
                $reservation->update(['status' => 'confirmed']);
            }

            $table = $reservation->table;

            // Mark table as unavailable when booked
            // It will be auto-released after 1 hour by the scheduled command
            $table->is_available = false;
            $table->save();

            // Clear availability cache
            Cache::forget("available_tables_{$reservation->reservation_date}_{$reservation->reservation_time}");

            // Send confirmation notifications
            $notificationService = app(NotificationService::class);
            $notificationService->sendReservationConfirmation($reservation);

            // Update queue metrics
            $processedToday = Cache::get('queue_processed_today', 0);
            Cache::put('queue_processed_today', $processedToday + 1, now()->endOfDay());

            Log::info('Reservation processed successfully', [
                'reservation_id' => $reservation->id,
                'table_id' => $reservation->table_id,
                'date' => $reservation->reservation_date,
                'time' => $reservation->reservation_time,
            ]);
        } catch (\Exception $e) {
            // Update failed metrics
            $failedToday = Cache::get('queue_failed_today', 0);
            Cache::put('queue_failed_today', $failedToday + 1, now()->endOfDay());
            
            Log::error('Failed to process reservation', [
                'error' => $e->getMessage(),
                'data' => $this->data,
            ]);
            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        // Update failed metrics
        $failedToday = Cache::get('queue_failed_today', 0);
        Cache::put('queue_failed_today', $failedToday + 1, now()->endOfDay());
        
        Log::error('Reservation job failed permanently', [
            'error' => $exception->getMessage(),
            'data' => $this->data,
        ]);
    }
}
