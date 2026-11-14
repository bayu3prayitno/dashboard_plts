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
        Schema::create('measurements', function (Blueprint $table) {
            // Sesuai ERD (erd.jpg)
            $table->id();
            $table->foreignId('device_id')->constrained('devices');
            $table->integer('packet_no');
            $table->decimal('temperature');
            $table->decimal('voltage');
            $table->decimal('current');
            $table->decimal('power');
            $table->decimal('irradiance');
            $table->integer('rssi');
            $table->decimal('snr');
            $table->decimal('packet_loss');
            $table->timestamp('recorded_at');
            $table->timestamp('created_at')->useCurrent(); // terisi otomatis saat data disimpan

            // Index pencarian historis per perangkat & rentang waktu (PRD 4.3)
            $table->index(['device_id', 'recorded_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('measurements');
    }
};
