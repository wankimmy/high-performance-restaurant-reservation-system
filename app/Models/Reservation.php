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
        'deposit_amount',
        'reservation_date',
        'reservation_time',
        'reservation_start_at',
        'reservation_end_at',
        'notes',
        'status',
        'ip_address',
        'user_agent',
        'otp_session_id',
        'otp_verified',
        'has_arrived',
        'arrived_at',
    ];

    protected $casts = [
        'reservation_date' => 'date',
        'reservation_start_at' => 'datetime',
        'reservation_end_at' => 'datetime',
        'pax' => 'integer',
        'deposit_amount' => 'decimal:2',
        'otp_verified' => 'boolean',
        'has_arrived' => 'boolean',
        'arrived_at' => 'datetime',
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

