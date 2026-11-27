<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kolom status & mqtt_status tidak pernah diperbarui sistem (hanya nilai awal saat gateway dibuat),
 * sehingga isinya menyesatkan. Keduanya dihitung saat dibaca oleh accessor di model Gateway:
 * status dari last_seen_at, mqtt_status dari heartbeat subscriber.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gateways', function (Blueprint $table) {
            $table->dropColumn(['status', 'mqtt_status']);
        });
    }

    public function down(): void
    {
        Schema::table('gateways', function (Blueprint $table) {
            $table->enum('status', ['online', 'offline'])->default('offline')->after('name');
            $table->enum('mqtt_status', ['connected', 'disconnected'])->default('disconnected')->after('status');
        });
    }
};
