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
        Schema::create('page_views', function (Blueprint $table) {
            $table->id();
            $table->string('path', 255);
            $table->string('route_name', 100)->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            // Identificadores anonimizados (hash) para medir visitantes únicos sin guardar datos personales.
            $table->string('session_hash', 64)->nullable();
            $table->string('ip_hash', 64)->nullable();
            $table->string('device', 20)->default('desktop');
            $table->string('referrer', 1024)->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->timestamp('viewed_at');

            $table->index('viewed_at');
            $table->index('path');
            $table->index('session_hash');
            $table->index('device');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('page_views');
    }
};
