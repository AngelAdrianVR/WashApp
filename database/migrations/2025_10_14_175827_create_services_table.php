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
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description');
            // Usamos decimal para manejar precios con centavos
            $table->decimal('price', 8, 2); 
            // Duración del servicio en minutos para calcular la disponibilidad
            $table->integer('duration_minutes'); 
            // Tipo de lavado para clasificar el servicio
            $table->enum('type', ['Seco', 'Agua']); 
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
