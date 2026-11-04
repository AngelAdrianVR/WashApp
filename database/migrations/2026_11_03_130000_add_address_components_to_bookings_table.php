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
            $table->string('street')->nullable()->after('address');
            $table->string('street_number', 30)->nullable()->after('street');
            $table->string('neighborhood')->nullable()->after('street_number');
            $table->string('postal_code', 10)->nullable()->after('neighborhood');
            $table->string('state', 80)->default('Jalisco')->after('postal_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['street', 'street_number', 'neighborhood', 'postal_code', 'state']);
        });
    }
};
