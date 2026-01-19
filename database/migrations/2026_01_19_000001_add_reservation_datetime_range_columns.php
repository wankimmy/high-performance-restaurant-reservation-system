<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dateTime('reservation_start_at')->nullable()->after('reservation_time');
            $table->dateTime('reservation_end_at')->nullable()->after('reservation_start_at');
        });

        DB::statement("
            UPDATE reservations
            SET
                reservation_start_at = TIMESTAMP(reservation_date, reservation_time),
                reservation_end_at = TIMESTAMP(reservation_date, reservation_time) + INTERVAL 105 MINUTE
            WHERE reservation_start_at IS NULL OR reservation_end_at IS NULL
        ");

        Schema::table('reservations', function (Blueprint $table) {
            $table->index(
                ['table_id', 'reservation_start_at', 'reservation_end_at', 'status'],
                'idx_reservations_overlap'
            );
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropIndex('idx_reservations_overlap');
        });

        Schema::table('reservations', function (Blueprint $table) {
            $table->dropColumn(['reservation_start_at', 'reservation_end_at']);
        });
    }
};
