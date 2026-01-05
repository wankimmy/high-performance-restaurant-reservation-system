<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reservation extends Model
{
    protected $fillable = [
        'table_id',
        'customer_name',
        'customer_email',
        'customer_phone',
        'pax',
        'reservation_date',
        'reservation_time',
        'notes',
        'status',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'reservation_date' => 'date',
        'pax' => 'integer',
    ];

    public function table(): BelongsTo
    {
        return $this->belongsTo(Table::class);
    }

    public function cancel(): void
    {
        $this->update(['status' => 'cancelled']);
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }
}

