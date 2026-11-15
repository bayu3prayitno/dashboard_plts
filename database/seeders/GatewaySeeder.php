<?php

namespace Database\Seeders;

use App\Models\Gateway;
use Illuminate\Database\Seeder;

class GatewaySeeder extends Seeder
{
    /**
     * Data awal gateway. Nama bersifat contoh, silakan sesuaikan.
     * Kode gateway dibuat otomatis oleh model.
     */
    public function run(): void
    {
        $gateways = [
            ['name' => 'Gateway 01'],
        ];

        foreach ($gateways as $gateway) {
            // Tidak membuat duplikat jika seeder dijalankan ulang
            Gateway::firstOrCreate(
                ['name' => $gateway['name']],
                [
                    'status' => 'offline',
                    'mqtt_status' => 'disconnected',
                    'internet_status' => 'disconnected',
                ],
            );
        }
    }
}
