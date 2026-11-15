<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            // Momento en que la reserva entró a la cola de asignación de personal.
            $table->timestamp('assignment_requested_at')->nullable()->after('status');

            // Tiempo estimado (en minutos) que indicó el operador al salir.
            $table->unsignedSmallInteger('arrival_eta_minutes')->nullable()->after('started_at');
            // Hora real en la que el operador salió hacia el domicilio.
            $table->timestamp('en_route_at')->nullable()->after('arrival_eta_minutes');
            // Ventana de llegada calculada (hora estimada ± 7 minutos).
            $table->timestamp('arrival_window_start')->nullable()->after('en_route_at');
            $table->timestamp('arrival_window_end')->nullable()->after('arrival_window_start');
            // Hora real de llegada al domicilio.
            $table->timestamp('arrived_at')->nullable()->after('arrival_window_end');

            // Confirmación del cliente de que el servicio quedó terminado.
            $table->timestamp('client_confirmed_at')->nullable()->after('completed_at');
            // Aromatizante de cortesía elegido por el cliente.
            $table->string('air_freshener', 60)->nullable()->after('client_confirmed_at');

            // Propina digital o en efectivo.
            $table->decimal('tip_amount', 8, 2)->nullable()->after('air_freshener');
            $table->string('tip_method', 20)->nullable()->after('tip_amount');

            // Puntos de fidelidad otorgados por esta reserva.
            $table->unsignedSmallInteger('points_awarded')->default(0)->after('tip_method');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn([
                'assignment_requested_at',
                'arrival_eta_minutes',
                'en_route_at',
                'arrival_window_start',
                'arrival_window_end',
                'arrived_at',
                'client_confirmed_at',
                'air_freshener',
                'tip_amount',
                'tip_method',
                'points_awarded',
            ]);
        });
    }
};
