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
        // This job is deprecated - use CreateReservation and ConfirmReservation instead
        // Keeping for backward compatibility but should not be used
        Log::warning('ProcessReservation job called - this is deprecated. Use CreateReservation and ConfirmReservation instead.');
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
