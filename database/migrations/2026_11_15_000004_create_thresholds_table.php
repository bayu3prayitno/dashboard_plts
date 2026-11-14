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
        Schema::create('thresholds', function (Blueprint $table) {
            // Sesuai ERD (erd.jpg)
            $table->id();
            // Nullable sesuai ERD: NULL untuk ambang batas standar sistem (PRD 4.5), terisi untuk ambang per perangkat
            $table->foreignId('device_id')->nullable()->constrained('devices');
            $table->enum('sensor_type', ['temperature', 'voltage', 'current', 'power', 'irradiance']);
            $table->decimal('min_value');
            $table->decimal('max_value');
            $table->boolean('is_active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('thresholds');
    }
};
