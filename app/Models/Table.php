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
        return $this->reservations()
            ->where('reservation_date', $date)
            ->where('reservation_time', $time)
            ->where('status', '!=', 'cancelled')
            ->exists();
    }
}

