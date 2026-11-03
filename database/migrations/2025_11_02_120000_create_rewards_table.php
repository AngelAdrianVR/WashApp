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
        Schema::create('rewards', function (Blueprint $table) {
            $table->id();
            // Nombre visible del premio (ej. "Lavado ecológico gratis")
            $table->string('name');
            // Descripción corta que se muestra en la tarjeta del premio
            $table->text('description');
            // Puntos que debe tener el usuario para canjearlo
            $table->unsignedInteger('points_cost');
            // La imagen del premio se gestiona con Spatie Media Library (colección "image")
            // Permite ocultar premios sin eliminarlos
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rewards');
    }
};
