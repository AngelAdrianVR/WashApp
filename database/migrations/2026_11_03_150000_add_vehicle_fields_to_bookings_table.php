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
            $table->string('vehicle_brand', 100)->nullable()->after('state');
            $table->string('vehicle_model', 100)->nullable()->after('vehicle_brand');
            $table->foreignId('vehicle_category_id')
                ->nullable()
                ->after('vehicle_model')
                ->constrained('vehicle_categories')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('vehicle_category_id');
            $table->dropColumn(['vehicle_brand', 'vehicle_model']);
        });
    }
};
