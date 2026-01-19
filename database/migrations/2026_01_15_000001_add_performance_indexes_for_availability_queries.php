<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Adds indexes to optimize frequently queried tables for availability checks:
     * - tables.capacity: Used in WHERE capacity >= pax queries
     * - reservations(reservation_date, status, table_id): Composite index for availability queries
     */
    public function up(): void
    {
        // Add index on tables.capacity for capacity-based filtering
        Schema::table('tables', function (Blueprint $table) {
            $table->index('capacity', 'idx_tables_capacity');
        });

        // Add composite index on reservations for availability queries
        // This optimizes: WHERE reservation_date = ? AND status != 'cancelled' AND table_id = ?
        Schema::table('reservations', function (Blueprint $table) {
            // Composite index for date + status filtering (most common query pattern)
            $table->index(['reservation_date', 'status'], 'idx_reservations_date_status');
            
            // Composite index for date + status + table_id (for eager loading with reservations)
            $table->index(['reservation_date', 'status', 'table_id'], 'idx_reservations_date_status_table');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tables', function (Blueprint $table) {
            $table->dropIndex('idx_tables_capacity');
        });

        Schema::table('reservations', function (Blueprint $table) {
            $table->dropIndex('idx_reservations_date_status');
            $table->dropIndex('idx_reservations_date_status_table');
        });
    }
};
