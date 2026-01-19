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
        $requestedStart = \Carbon\Carbon::parse($date . ' ' . $time);
        $requestedEnd = $requestedStart->copy()->addMinutes(105);

        return $this->reservations()
            ->whereIn('status', ['pending', 'confirmed'])
            ->where('reservation_start_at', '<', $requestedEnd)
            ->where('reservation_end_at', '>', $requestedStart)
            ->exists();
    }

}

