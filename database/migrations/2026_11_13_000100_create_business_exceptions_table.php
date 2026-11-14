<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_exceptions', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->string('label')->nullable();
            // true = descanso todo el día; false = descanso parcial (start_time/end_time).
            $table->boolean('is_closed')->default(true);
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->timestamps();
            $table->index('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_exceptions');
    }
};
