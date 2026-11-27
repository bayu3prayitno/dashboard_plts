<?php

namespace Database\Seeders;

use App\Models\Gateway;
use App\Models\Device;
use App\Models\Measurement;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;

class DummyDataSeeder extends Seeder
{
    public function run(): void
    {
        $now = CarbonImmutable::now('UTC');
        $bucket = $now->setSecond(0)->setMicrosecond(0);
        $bucket = $bucket->setMinute($bucket->minute >= 30 ? 30 : 0);

        $gateway = Gateway::firstOrCreate(
            ['name' => 'Gateway Demo PLTS'],
            [
                'is_online' => true,
                'is_connected' => true,
                'internet_last_seen_at' => $now,
                'last_seen_at' => $now,
            ],
        );

        $gateway->update(['is_online' => true, 'is_connected' => true, 'last_seen_at' => $now, 'internet_last_seen_at' => $now]);

        $nodeDefinitions = [
            1 => ['name' => 'Node Panel 01', 'location' => 'Blok A - String 1', 'offset' => 0.0],
        ];

        foreach ($nodeDefinitions as $nodeId => $definition) {
            $device = Device::updateOrCreate(
                ['gateway_id' => $gateway->id, 'node_id' => $nodeId],
                [
                    'name' => $definition['name'],
                    'location' => $definition['location'],
                    'is_active' => true,
                    'is_online' => true,
                    'last_seen_at' => $now,
                ],
            );

            Measurement::where('device_id', $device->id)->delete();

            $rows = [];
            $packetNo = 100000 + ($nodeId * 1000);
            $start = $bucket->subDays(7);

            for ($i = 0; $i <= 336; $i++) {
                $recordedAt = $start->addMinutes($i * 30);
                $localHour = (int) $recordedAt->timezone('Asia/Jakarta')->format('G');
                $dayPhase = sin(($i / 336) * pi());
                $sunCurve = $localHour >= 6 && $localHour <= 18
                    ? max(0, sin(($localHour - 6) / 12 * pi()))
                    : 0;

                $jitter = sin(($i * 0.37) + $definition['offset']);
                $irradiance = round(max(0, $sunCurve * (780 + 90 * $dayPhase) + $jitter * 18), 2);
                $temperature = round(27.0 + ($irradiance / 850 * 8.5) + sin(($i * 0.23) + $definition['offset']) * 0.8, 2);
                $voltage = round(23.8 + ($irradiance / 850 * 2.1) + sin(($i * 0.19) + $definition['offset']) * 0.12, 2);
                $current = round(max(0.1, ($irradiance / 850 * 8.2) + sin(($i * 0.31) + $definition['offset']) * 0.35), 2);
                $power = round($voltage * $current * 0.96, 2);
                $rssi = (int) round(-58 - abs($jitter) * 8 - (($nodeId - 1) * 2));
                $snr = round(12.5 - abs($jitter) * 2.5, 2);
                $packetLoss = round(max(0, 0.8 + abs(sin(($i * 0.11) + $definition['offset'])) * 1.8), 2);

                $rows[] = [
                    'device_id' => $device->id,
                    'packet_no' => $packetNo + $i,
                    'temperature' => $temperature,
                    'voltage' => $voltage,
                    'current' => $current,
                    'power' => $power,
                    'irradiance' => $irradiance,
                    'rssi' => $rssi,
                    'snr' => $snr,
                    'packet_loss' => $packetLoss,
                    'recorded_at' => $recordedAt,
                    'created_at' => $recordedAt,
                ];
            }

            foreach (array_chunk($rows, 500) as $chunk) {
                Measurement::insert($chunk);
            }

            $this->command?->info("{$definition['name']}: {$device->measurements()->count()} pengukuran");
        }

        Cache::forget('dashboard_trend_' . $now->timezone('Asia/Jakarta')->format('Y-m-d-H'));
        $this->command?->info('Data dummy grafik berhasil dibuat untuk periode 7 hari.');
    }
}
