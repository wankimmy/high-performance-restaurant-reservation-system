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
        Schema::table('restaurant_settings', function (Blueprint $table) {
            $table->integer('time_slot_interval')->default(30)->after('closing_time')->comment('Time slot interval in minutes');
        });

        // Update existing record with default interval
        DB::table('restaurant_settings')->update(['time_slot_interval' => 30]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('restaurant_settings', function (Blueprint $table) {
            $table->dropColumn('time_slot_interval');
        });
    }
};
