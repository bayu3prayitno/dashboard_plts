<?php

namespace Database\Seeders;

use App\Models\Gateway;
use Illuminate\Database\Seeder;

class GatewaySeeder extends Seeder
{
    /**
     * Data awal gateway. Nama dan lokasi bersifat contoh (sama dengan data tiruan di dashboard),
     * silakan sesuaikan dengan lokasi lapangan. Kode gateway dibuat otomatis oleh model.
     */
    public function run(): void
    {
        $gateways = [
            ['name' => 'Gateway 01', 'location' => 'Politeknik Negeri Semarang'],
        ];

        foreach ($gateways as $gateway) {
            // Tidak membuat duplikat jika seeder dijalankan ulang
            Gateway::firstOrCreate(
                ['name' => $gateway['name']],
                [
                    'location' => $gateway['location'],
                    'status' => 'offline',
                    'mqtt_status' => 'disconnected',
                    'internet_status' => 'disconnected',
                ],
            );
        }
    }
}
