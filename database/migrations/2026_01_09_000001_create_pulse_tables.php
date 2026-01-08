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
        Schema::create('pulse_aggregates', function (Blueprint $table) {
            $table->id();
            $table->string('type', 255);
            $table->string('aggregate', 255);
            $table->string('key', 255);
            $table->unsignedBigInteger('key_hash')->index();
            $table->unsignedInteger('bucket');
            $table->string('period', 255);
            $table->decimal('value', 20, 2)->nullable();
            $table->unsignedInteger('count')->nullable();
            $table->timestamps();

            $table->unique(['type', 'aggregate', 'key_hash', 'bucket', 'period'], 'pulse_aggregates_unique');
            $table->index(['type', 'aggregate', 'period', 'bucket']);
        });

        Schema::create('pulse_entries', function (Blueprint $table) {
            $table->id();
            $table->string('type', 255);
            $table->string('key', 255);
            $table->unsignedBigInteger('key_hash')->index();
            $table->timestamp('timestamp');
            $table->json('value')->nullable();
            $table->timestamps();

            $table->index(['type', 'key_hash', 'timestamp']);
        });

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

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pulse_values');
        Schema::dropIfExists('pulse_entries');
        Schema::dropIfExists('pulse_aggregates');
    }
};
