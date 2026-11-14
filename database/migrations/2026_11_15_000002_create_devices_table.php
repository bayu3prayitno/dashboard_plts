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
        Schema::create('devices', function (Blueprint $table) {
            // Sesuai ERD (erd.jpg)
            $table->id();
            $table->foreignId('gateway_id')->constrained('gateways');
            $table->integer('node_id');
            $table->string('name');
            $table->string('location');
            $table->boolean('is_active');
            $table->timestamp('last_seen_at')->nullable(); // kosong sebelum Node pernah mengirim data
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('devices');
    }
};
