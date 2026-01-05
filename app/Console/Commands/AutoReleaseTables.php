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
    protected $description = 'Automatically release tables 1 hour after reservation time';

    public function handle(): int
    {
        $this->info('Checking for tables to release...');
        
        $releasedCount = 0;
        
        // Get all confirmed reservations that are past their reservation time + 1 hour
        $now = now();
        
        $reservations = Reservation::where('status', 'confirmed')
            ->whereNotNull('reservation_date')
            ->whereNotNull('reservation_time')
            ->with('table')
            ->get()
            ->filter(function ($reservation) use ($now) {
                // Calculate reservation datetime
                $reservationDateTime = \Carbon\Carbon::parse($reservation->reservation_date->format('Y-m-d') . ' ' . $reservation->reservation_time);
                
                // Check if 1 hour has passed since reservation time
                return $reservationDateTime->addHour()->isPast();
            });

        foreach ($reservations as $reservation) {
            $table = $reservation->table;
            
            if ($table && !$table->is_available) {
                // Check if there are no other active reservations for this table
                $hasActiveReservations = Reservation::where('table_id', $table->id)
                    ->where('status', '!=', 'cancelled')
                    ->where(function ($query) use ($reservation) {
                        $query->where('reservation_date', '>', $reservation->reservation_date)
                            ->orWhere(function ($q) use ($reservation) {
                                $q->where('reservation_date', $reservation->reservation_date)
                                    ->where('reservation_time', '>', $reservation->reservation_time);
                            });
                    })
                    ->exists();

                // Only release if no future reservations
                // Note: Overlapping reservations shouldn't exist due to availability checks,
                // but we check for future reservations to ensure we don't release a table
                // that has another booking coming up
                if (!$hasActiveReservations) {
                    $table->is_available = true;
                    $table->save();
                    
                    // Clear all availability caches for this date
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
                    
                    $releasedCount++;
                    
                    $this->line("Released table: {$table->name} (Reservation: {$reservation->reservation_date->format('Y-m-d')} {$reservation->reservation_time})");
                    
                    Log::info('Table auto-released', [
                        'table_id' => $table->id,
                        'table_name' => $table->name,
                        'reservation_id' => $reservation->id,
                    ]);
                }
            }
        }

        if ($releasedCount > 0) {
            $this->info("Released {$releasedCount} table(s).");
        } else {
            $this->info('No tables to release.');
        }

        return Command::SUCCESS;
    }
}
