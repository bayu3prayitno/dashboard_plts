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
        Schema::create('alerts', function (Blueprint $table) {
            // Sesuai ERD (erd.jpg)
            $table->id();
            $table->foreignId('device_id')->constrained('devices');
            $table->foreignId('handled_by')->nullable()->constrained('users'); // NULL sebelum ada yang menangani
            // Tipe alert: offline & threshold (PRD 4.4 menyebut batas atas dan bawah)
            $table->enum('alert_type', ['offline', 'threshold_high', 'threshold_low']);
            // Sensor, nilai terukur, dan nilai ambang hanya relevan untuk alert threshold (kosong untuk offline)
            $table->enum('sensor_type', ['temperature', 'voltage', 'current', 'power', 'irradiance'])->nullable();
            $table->decimal('recorded_value')->nullable();
            $table->decimal('threshold_value')->nullable();
            $table->enum('status', ['new', 'acknowledged', 'resolved']);
            $table->text('resolution_note')->nullable(); // diisi saat alert diselesaikan
            $table->timestamp('triggered_at');
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alerts');
    }
};
