<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('restaurant_settings', function (Blueprint $table) {
            $table->id();
            $table->time('opening_time')->default('09:00:00');
            $table->time('closing_time')->default('22:00:00');
            $table->decimal('deposit_per_pax', 10, 2)->default(0.00);
            $table->timestamps();
        });

        // Insert default settings
        // Note: time_slot_interval will be added in a later migration
        DB::table('restaurant_settings')->insert([
            'opening_time' => '09:00:00',
            'closing_time' => '22:00:00',
            'deposit_per_pax' => 0.00,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('restaurant_settings');
    }
};
