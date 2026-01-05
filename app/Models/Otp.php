<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Otp extends Model
{
    protected $fillable = [
        'phone_number',
        'otp_code',
        'session_id',
        'reservation_id',
        'is_verified',
        'expires_at',
        'attempts',
    ];

    protected $casts = [
        'is_verified' => 'boolean',
        'expires_at' => 'datetime',
        'attempts' => 'integer',
    ];

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isValid(): bool
    {
        return !$this->is_verified && !$this->isExpired() && $this->attempts < 5;
    }
}
