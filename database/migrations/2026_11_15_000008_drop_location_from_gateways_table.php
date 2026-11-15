<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rangkuman kebutuhan hanya mencantumkan lokasi untuk perangkat (node sensor),
 * tidak untuk gateway. Kolom lokasi pada gateways dihapus.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gateways', function (Blueprint $table) {
            $table->dropColumn('location');
        });
    }

    public function down(): void
    {
        Schema::table('gateways', function (Blueprint $table) {
            $table->string('location')->nullable();
        });
    }
};
