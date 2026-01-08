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
        if (!Schema::hasTable('pulse_values')) {
            Schema::create('pulse_values', function (Blueprint $table) {
                $table->id();
                $table->string('type', 255);
                $table->string('key', 255);
                $table->unsignedBigInteger('key_hash')->index();
                $table->timestamp('timestamp');
                $table->json('value')->nullable();
                $table->timestamps();

                $table->index(['type', 'key_hash', 'timestamp']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pulse_values');
    }
};
