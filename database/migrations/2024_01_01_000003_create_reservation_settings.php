<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservation_settings', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->boolean('is_open')->default(true);
            $table->time('opening_time')->nullable();
            $table->time('closing_time')->nullable();
            $table->timestamps();
            
            $table->unique('date');
            $table->index('is_open');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservation_settings');
    }
};

