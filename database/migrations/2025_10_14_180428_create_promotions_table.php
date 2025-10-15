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
        Schema::create('promotions', function (Blueprint $table) {
            $table->id();
            // El código que el usuario introducirá. Debe ser único.
            $table->string('code')->unique();
            $table->text('description')->nullable();
            // El valor del descuento (ej. 10.00 o 15)
            $table->decimal('discount_value', 8, 2);
            // Si es un descuento fijo o un porcentaje
            $table->enum('discount_type', ['Fijo', 'Porcentaje']);
            // Fecha de expiración del código (opcional)
            $table->timestamp('expires_at')->nullable();
            // Límite de usos totales (opcional)
            $table->integer('max_uses')->nullable();
            // Contador de cuántas veces se ha usado
            $table->integer('current_uses')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promotions');
    }
};
