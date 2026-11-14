<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * La imagen del premio pasa a gestionarse con Spatie Media Library
     * (colección "image"), por lo que la columna deja de ser necesaria.
     */
    public function up(): void
    {
        Schema::table('rewards', function (Blueprint $table) {
            if (Schema::hasColumn('rewards', 'image_path')) {
                $table->dropColumn('image_path');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rewards', function (Blueprint $table) {
            $table->string('image_path')->nullable()->after('points_cost');
        });
    }
};
