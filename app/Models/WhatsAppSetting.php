<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class WhatsAppSetting extends Model
{
    protected $table = 'whatsapp_settings';
    
    protected $fillable = [
        'is_enabled',
        'service_url',
        'status',
        'qr_code',
        'last_connected_at',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'last_connected_at' => 'datetime',
    ];

    /**
     * Get settings (singleton pattern with caching)
     */
    public static function getSettings(): self
    {
        return Cache::remember('whatsapp_settings', 3600, function () {
            return self::first() ?? self::create([
                'is_enabled' => false,
                'service_url' => env('WHATSAPP_SERVICE_URL', 'http://whatsapp-service:3001'),
                'status' => 'disconnected',
            ]);
        });
    }

    /**
     * Clear settings cache
     */
    public static function clearCache(): void
    {
        Cache::forget('whatsapp_settings');
    }

    /**
     * Check if WhatsApp is connected
     */
    public function isConnected(): bool
    {
        return $this->is_enabled && $this->status === 'connected';
    }
}
