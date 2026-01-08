<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('whatsapp_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('is_enabled')->default(false);
            $table->string('service_url')->nullable()->comment('URL of the WhatsApp Baileys service');
            $table->string('status')->default('disconnected')->comment('connected, disconnected, qr_ready, error');
            $table->text('qr_code')->nullable();
            $table->timestamp('last_connected_at')->nullable();
            $table->timestamps();
        });

        // Insert default settings
        \Illuminate\Support\Facades\DB::table('whatsapp_settings')->insert([
            'is_enabled' => false,
            'service_url' => env('WHATSAPP_SERVICE_URL', 'http://whatsapp-service:3001'),
            'status' => 'disconnected',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_settings');
    }
};
