<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('table_id')->constrained()->onDelete('cascade');
            $table->string('customer_name');
            $table->string('customer_email');
            $table->string('customer_phone');
            $table->integer('pax');
            $table->date('reservation_date');
            $table->time('reservation_time');
            $table->string('notes', 100)->nullable();
            $table->enum('status', ['pending', 'confirmed', 'cancelled'])->default('confirmed');
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();
            
            $table->index(['reservation_date', 'reservation_time']);
            $table->index('status');
            $table->index('table_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};

