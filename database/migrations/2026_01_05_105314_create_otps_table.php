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
        Schema::create('otps', function (Blueprint $table) {
            $table->id();
            $table->string('phone_number', 20);
            $table->string('otp_code', 6);
            $table->string('session_id', 100)->unique();
            $table->foreignId('reservation_id')->nullable()->constrained()->onDelete('cascade');
            $table->boolean('is_verified')->default(false);
            $table->timestamp('expires_at');
            $table->integer('attempts')->default(0);
            $table->timestamps();
            
            $table->index(['phone_number', 'is_verified']);
            $table->index('session_id');
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('otps');
    }
};
