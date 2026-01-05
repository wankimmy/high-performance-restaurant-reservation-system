<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tables', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('capacity');
            $table->boolean('is_available')->default(true);
            $table->timestamps();
            $table->index('is_available');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tables');
    }
};

