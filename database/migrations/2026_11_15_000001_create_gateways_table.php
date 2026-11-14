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
        Schema::create('gateways', function (Blueprint $table) {
            // Sesuai ERD (erd.jpg)
            $table->id();
            $table->string('gateway_code')->unique();
            $table->string('name');
            $table->string('location');
            $table->enum('status', ['online', 'offline']);
            $table->enum('mqtt_status', ['connected', 'disconnected']);
            $table->enum('internet_status', ['connected', 'disconnected']);
            $table->timestamp('last_seen_at')->nullable(); // kosong sebelum Gateway pernah terhubung
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gateways');
    }
};
