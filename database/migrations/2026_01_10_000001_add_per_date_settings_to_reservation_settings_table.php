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
        Schema::table('reservation_settings', function (Blueprint $table) {
            $table->integer('time_slot_interval')->nullable()->after('closing_time');
            $table->decimal('deposit_per_pax', 10, 2)->nullable()->after('time_slot_interval');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reservation_settings', function (Blueprint $table) {
            $table->dropColumn(['time_slot_interval', 'deposit_per_pax']);
        });
    }
};
