<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * internet_status hanya salinan dari internet_last_seen_at dan bisa basi di antara jalannya job
 * terjadwal. Status internet gateway kini dihitung dari internet_last_seen_at oleh accessor Gateway.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gateways', function (Blueprint $table) {
            $table->dropColumn('internet_status');
        });
    }

    public function down(): void
    {
        Schema::table('gateways', function (Blueprint $table) {
            $table->enum('internet_status', ['connected', 'disconnected'])->default('disconnected')->after('name');
        });
    }
};
