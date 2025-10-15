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
        Schema::create('user_rewards', function (Blueprint $table) {
            $table->id();
            // Usuario que ganó la recompensa
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            // La promoción que se le otorgó (ej. un código de "LAVADOGRATIS")
            $table->foreignId('promotion_id')->constrained('promotions')->onDelete('cascade');
            // La reserva que generó esta recompensa
            $table->foreignId('booking_id')->nullable()->constrained('bookings')->onDelete('set null');
            // Para saber si el usuario ya utilizó este cupón
            $table->boolean('is_claimed')->default(false);
            $table->timestamp('claimed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_rewards');
    }
};
