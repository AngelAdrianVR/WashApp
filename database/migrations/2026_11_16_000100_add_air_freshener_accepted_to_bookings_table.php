<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            // El cliente acepta (o rechaza) el aromatizante de cortesía al confirmar el servicio.
            $table->boolean('air_freshener_accepted')->nullable()->after('air_freshener');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('air_freshener_accepted');
        });
    }
};
