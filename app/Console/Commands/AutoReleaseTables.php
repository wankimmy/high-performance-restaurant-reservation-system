<?php

namespace App\Console\Commands;

use App\Models\Reservation;
use App\Models\Table;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AutoReleaseTables extends Command
{
    protected $signature = 'tables:auto-release';
    protected $description = 'Clear availability cache for past reservations (availability is now determined by reservations only)';

    public function handle(): int
    {
        $this->info('Clearing availability cache for past reservations...');
        
        $clearedCount = 0;
        
        // Get all confirmed reservations that are past their reservation time + 1 hour 45 minutes
        $now = now();
        
        $reservations = Reservation::where('status', 'confirmed')
            ->whereNotNull('reservation_date')
            ->whereNotNull('reservation_time')
            ->get()
            ->filter(function ($reservation) use ($now) {
                // Calculate reservation datetime
                $reservationDateTime = \Carbon\Carbon::parse($reservation->reservation_date->format('Y-m-d') . ' ' . $reservation->reservation_time);
                
                // Check if 1 hour 45 minutes has passed since reservation time
                return $reservationDateTime->addMinutes(105)->isPast();
            });

        foreach ($reservations as $reservation) {
            // Clear all availability caches for this date
            // This ensures fresh availability data is fetched on next request
            $date = $reservation->reservation_date->format('Y-m-d');
            for ($hour = 16; $hour <= 21; $hour++) {
                for ($minute = 0; $minute < 60; $minute += 30) {
                    $time = sprintf('%02d:%02d', $hour, $minute);
                    Cache::forget("available_tables_{$date}_{$time}");
                    // Also clear with pax variations
                    for ($pax = 1; $pax <= 20; $pax++) {
                        Cache::forget("available_tables_{$date}_{$time}_{$pax}");
                    }
                }
            }
            
            $clearedCount++;
        }

        if ($clearedCount > 0) {
            $this->info("Cleared cache for {$clearedCount} reservation(s).");
        } else {
            $this->info('No cache entries to clear.');
        }

        return Command::SUCCESS;
    }
}
