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
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();

            // Relación con el cliente (si está logueado). Nulo para invitados.
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');

            // Relación con el empleado asignado a la cita.
            $table->foreignId('employee_id')->nullable()->constrained('users')->onDelete('set null');
            
            // Datos del cliente si la reserva es como invitado
            $table->string('guest_name')->nullable();
            $table->string('guest_email')->nullable();
            $table->string('guest_phone')->nullable();

            // Información de la ubicación del servicio
            $table->text('address');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            // Fecha y hora agendada para la cita
            $table->dateTime('scheduled_at');

            // Estado actual de la cita para el seguimiento
            $table->enum('status', ['pending', 'confirmed', 'on_way', 'in_progress', 'completed', 'cancelled'])->default('pending');
            
            // Precio total calculado al momento de la reserva
            $table->decimal('total_price', 8, 2);

            // Notas adicionales del cliente o del empleado
            $table->text('notes')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
