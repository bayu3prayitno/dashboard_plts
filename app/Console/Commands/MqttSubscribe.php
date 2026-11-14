<?php

namespace App\Console\Commands;

use App\Services\TelemetryIngestor;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use PhpMqtt\Client\ConnectionSettings;
use PhpMqtt\Client\MqttClient;
use Throwable;

/**
 * Berlangganan topik MQTT dari Gateway dan menyimpan setiap paket data.
 * Jalankan dengan: php artisan mqtt:subscribe (biarkan tetap berjalan, mis. lewat supervisor).
 */
class MqttSubscribe extends Command
{
    protected $signature = 'mqtt:subscribe';

    protected $description = 'Berlangganan MQTT dari Gateway dan menyimpan data sensor';

    public function handle(TelemetryIngestor $ingestor): int
    {
        $config = config('mqtt');

        while (true) {
            try {
                $settings = (new ConnectionSettings)
                    ->setUsername($config['username'])
                    ->setPassword($config['password'])
                    ->setUseTls($config['tls']);

                $client = new MqttClient($config['host'], $config['port'], $config['client_id']);
                $client->connect($settings, true);

                $this->info("Terhubung ke broker {$config['host']}:{$config['port']}, topik {$config['topic']}");

                $client->subscribe($config['topic'], function (string $topic, string $message) use ($ingestor) {
                    $this->handleMessage($topic, $message, $ingestor);
                }, 0);

                $client->loop(true);
            } catch (Throwable $e) {
                Log::error('MQTT: koneksi terputus, mencoba lagi', ['error' => $e->getMessage()]);
                $this->warn('Koneksi terputus: '.$e->getMessage().'. Mencoba lagi dalam 5 detik...');
                sleep(5);
            }
        }
    }

    private function handleMessage(string $topic, string $message, TelemetryIngestor $ingestor): void
    {
        // omahiot/gateway/{kode_gateway}/data
        $segments = explode('/', $topic);
        $gatewayCode = $segments[2] ?? null;

        $payload = json_decode($message, true);
        if (! is_array($payload) || $gatewayCode === null) {
            Log::warning('MQTT: pesan bukan JSON yang valid', ['topic' => $topic]);

            return;
        }

        try {
            $ingestor->ingest($gatewayCode, $payload);
        } catch (Throwable $e) {
            // Satu pesan yang gagal tidak boleh menghentikan langganan
            Log::error('MQTT: gagal menyimpan data', ['topic' => $topic, 'error' => $e->getMessage()]);
        }
    }
}
