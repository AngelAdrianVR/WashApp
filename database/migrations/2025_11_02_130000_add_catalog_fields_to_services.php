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
        // Catálogo de categorías de vehículo (Sedán, SUV, Pickup…)
        Schema::create('vehicle_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('services', function (Blueprint $table) {
            // Lista de características incluidas (bullets del servicio)
            $table->json('features')->nullable()->after('description');
            // Distingue paquetes de lavado de los servicios adicionales (upselling)
            $table->boolean('is_addon')->default(false)->after('type');
            // Puntos de fidelidad que otorga el servicio
            $table->unsignedInteger('points')->default(0)->after('is_addon');
            // Orden de aparición en la landing y el panel
            $table->unsignedInteger('sort_order')->default(0)->after('points');
        });

        // Precio y duración de cada paquete según la categoría del vehículo
        Schema::create('service_vehicle_category', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained('services')->cascadeOnDelete();
            $table->foreignId('vehicle_category_id')->constrained('vehicle_categories')->cascadeOnDelete();
            $table->decimal('price', 8, 2);
            $table->unsignedInteger('duration_minutes');
            $table->timestamps();

            $table->unique(['service_id', 'vehicle_category_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_vehicle_category');

        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn(['features', 'is_addon', 'points', 'sort_order']);
        });

        Schema::dropIfExists('vehicle_categories');
    }
};
