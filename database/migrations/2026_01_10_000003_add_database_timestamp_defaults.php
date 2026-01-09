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
        // List of tables that need timestamp defaults
        $tables = [
            'users',
            'tables',
            'reservations',
            'reservation_settings',
            'rate_limits',
            'otps',
            'restaurant_settings',
            'whatsapp_settings',
        ];

        foreach ($tables as $tableName) {
            if (Schema::hasTable($tableName)) {
                try {
                    // Update created_at column to have database-level default
                    if (Schema::hasColumn($tableName, 'created_at')) {
                        // Check current column definition to preserve nullable
                        $columnInfo = DB::select("SHOW COLUMNS FROM `{$tableName}` WHERE Field = 'created_at'");
                        if (!empty($columnInfo)) {
                            $isNullable = $columnInfo[0]->Null === 'YES' ? 'NULL' : 'NOT NULL';
                            DB::statement("ALTER TABLE `{$tableName}` MODIFY COLUMN `created_at` TIMESTAMP {$isNullable} DEFAULT CURRENT_TIMESTAMP");
                        }
                    }

                    // Update updated_at column to have database-level default with auto-update
                    if (Schema::hasColumn($tableName, 'updated_at')) {
                        $columnInfo = DB::select("SHOW COLUMNS FROM `{$tableName}` WHERE Field = 'updated_at'");
                        if (!empty($columnInfo)) {
                            $isNullable = $columnInfo[0]->Null === 'YES' ? 'NULL' : 'NOT NULL';
                            DB::statement("ALTER TABLE `{$tableName}` MODIFY COLUMN `updated_at` TIMESTAMP {$isNullable} DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
                        }
                    }
                } catch (\Exception $e) {
                    // Log error but continue with other tables
                    \Log::warning("Failed to update timestamps for table {$tableName}: " . $e->getMessage());
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to Laravel's default timestamp behavior
        $tables = [
            'users',
            'tables',
            'reservations',
            'reservation_settings',
            'rate_limits',
            'otps',
            'restaurant_settings',
            'whatsapp_settings',
        ];

        foreach ($tables as $tableName) {
            if (Schema::hasTable($tableName)) {
                if (Schema::hasColumn($tableName, 'created_at')) {
                    DB::statement("ALTER TABLE `{$tableName}` MODIFY COLUMN `created_at` TIMESTAMP NULL");
                }

                if (Schema::hasColumn($tableName, 'updated_at')) {
                    DB::statement("ALTER TABLE `{$tableName}` MODIFY COLUMN `updated_at` TIMESTAMP NULL");
                }
            }
        }
    }
};
