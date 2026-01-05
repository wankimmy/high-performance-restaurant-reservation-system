<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rate_limits', function (Blueprint $table) {
            $table->id();
            $table->string('identifier'); // IP address or user identifier
            $table->string('key');
            $table->integer('attempts')->default(1);
            $table->timestamp('expires_at');
            $table->timestamps();
            
            $table->index(['identifier', 'key']);
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rate_limits');
    }
};

