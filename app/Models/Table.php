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
        
        // Check if the requested time slot overlaps with any existing reservation's 1 hour 45 minute block
        // A reservation at 9:00am blocks the table from 9:00am to 10:45am
        $requestedDateTime = \Carbon\Carbon::parse($date . ' ' . $time);
        $requestedEndTime = $requestedDateTime->copy()->addMinutes(105); // Requested slot also lasts 1 hour 45 minutes
        
        $conflictingReservation = $this->reservations()
            ->where('reservation_date', $date)
            ->where('status', '!=', 'cancelled')
            ->get()
            ->filter(function ($reservation) use ($requestedDateTime, $requestedEndTime) {
                $reservationDateTime = \Carbon\Carbon::parse($reservation->reservation_date->format('Y-m-d') . ' ' . $reservation->reservation_time);
                $reservationEndTime = $reservationDateTime->copy()->addMinutes(105); // 1 hour 45 minutes
                
                // Check if the requested time slot overlaps with the reservation block
                // Overlap occurs if: requested start < reservation end AND requested end > reservation start
                return $requestedDateTime->lt($reservationEndTime) && $requestedEndTime->gt($reservationDateTime);
            })
            ->first();
        
        return $conflictingReservation !== null;
    }

}

