<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Table extends Model
{
    protected $fillable = [
        'name',
        'capacity',
        'is_available',
    ];

    protected $casts = [
        'is_available' => 'boolean',
    ];

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    public function hasReservationAt(string $date, string $time): bool
    {
        // Check if there's a reservation at the exact time
        $exactMatch = $this->reservations()
            ->where('reservation_date', $date)
            ->where('reservation_time', $time)
            ->where('status', '!=', 'cancelled')
            ->exists();
        
        if ($exactMatch) {
            return true;
        }
        
        // Check if the requested time falls within any existing reservation's 1-hour block
        // A reservation at 9:00am blocks the table from 9:00am to 9:59am (or 9:00am to 10:00am)
        $requestedDateTime = \Carbon\Carbon::parse($date . ' ' . $time);
        
        $conflictingReservation = $this->reservations()
            ->where('reservation_date', $date)
            ->where('status', '!=', 'cancelled')
            ->get()
            ->filter(function ($reservation) use ($requestedDateTime) {
                $reservationDateTime = \Carbon\Carbon::parse($reservation->reservation_date->format('Y-m-d') . ' ' . $reservation->reservation_time);
                $reservationEndTime = $reservationDateTime->copy()->addHour();
                
                // Check if requested time falls within the reservation block (9:00am to 10:00am)
                // Requested time should be >= reservation start and < reservation end
                return $requestedDateTime->gte($reservationDateTime) && $requestedDateTime->lt($reservationEndTime);
            })
            ->first();
        
        return $conflictingReservation !== null;
    }

    /**
     * Check if table should be available based on reservations
     */
    public function shouldBeAvailable(): bool
    {
        // If manually set to unavailable, respect that
        if (!$this->is_available) {
            // Check if there are any active reservations
            $hasActiveReservations = $this->reservations()
                ->where('status', '!=', 'cancelled')
                ->where(function ($query) {
                    $query->where('reservation_date', '>', now()->format('Y-m-d'))
                        ->orWhere(function ($q) {
                            $q->where('reservation_date', now()->format('Y-m-d'))
                                ->where('reservation_time', '>', now()->format('H:i'));
                        });
                })
                ->exists();

            // If no active reservations, should be available
            return !$hasActiveReservations;
        }

        return true;
    }
}

