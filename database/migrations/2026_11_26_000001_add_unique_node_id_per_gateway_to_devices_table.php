<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ID Node unik di dalam satu gateway, tetapi boleh sama di gateway berbeda.
 * Topik MQTT vpp/kampus/{kode_gateway}/node{node_id} hanya menunjuk satu perangkat bila pasangan ini unik.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->unique(['gateway_id', 'node_id'], 'devices_gateway_id_node_id_unique');
        });
    }

    public function down(): void
    {
        // 
        Schema::table('devices', function (Blueprint $table) {
            $table->dropForeign(['gateway_id']);
            $table->dropUnique('devices_gateway_id_node_id_unique');
        });

        Schema::table('devices', function (Blueprint $table) {
            $table->foreign('gateway_id')->references('id')->on('gateways');
        });
    }
};
