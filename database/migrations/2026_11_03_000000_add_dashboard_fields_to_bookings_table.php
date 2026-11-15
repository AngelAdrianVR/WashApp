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
        Schema::table('bookings', function (Blueprint $table) {
            // Método de pago con el que se liquidó (o liquidará) la cita.
            $table->string('payment_method', 30)->nullable()->after('status');
            // Momento real en que el empleado comenzó el servicio.
            $table->timestamp('started_at')->nullable()->after('scheduled_at');
            // Momento real en que el servicio se finalizó (base para los ingresos).
            $table->timestamp('completed_at')->nullable()->after('started_at');

            $table->index(['status', 'scheduled_at']);
            $table->index(['status', 'completed_at']);
            $table->index(['employee_id', 'scheduled_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropIndex(['status', 'scheduled_at']);
            $table->dropIndex(['status', 'completed_at']);
            $table->dropIndex(['employee_id', 'scheduled_at']);
            $table->dropColumn(['payment_method', 'started_at', 'completed_at']);
        });
    }
};
