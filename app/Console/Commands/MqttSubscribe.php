<?php

namespace App\Console\Commands;

use App\Models\Gateway;
use App\Services\TelemetryIngestor;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
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

    /** Interval heartbeat (detik) yang dibaca web untuk menentukan status MQTT. */
    private const HEARTBEAT_INTERVAL = 5;

    /** Seberapa sering (detik) kode diperiksa apakah sudah berubah. */
    private const CODE_CHECK_INTERVAL = 30;

    public function handle(TelemetryIngestor $ingestor): int
    {
        $config = config('mqtt');
        $codeVersion = $this->codeVersion();

        while (true) {
            try {
                $settings = (new ConnectionSettings)
                    ->setUsername($config['username'])
                    ->setPassword($config['password'])
                    ->setUseTls($config['tls']);

                // subscriber dengan ID sama akan saling memutus koneksi.
                $clientId = $config['client_id'].'-'.getmypid();
                $client = new MqttClient($config['host'], $config['port'], $clientId);
                $client->connect($settings, true);

                $this->info("Terhubung ke broker {$config['host']}:{$config['port']}, topik {$config['topic']}");

                $client->subscribe($config['topic'], function (string $topic, string $message) use ($ingestor) {
                    $this->handleMessage($topic, $message, $ingestor);
                }, 0);

                // Loop manual agar heartbeat tetap dikirim selama menunggu pesan
                $lastHeartbeat = 0.0;
                $lastCodeCheck = microtime(true);
                while (true) {
                    $client->loopOnce(microtime(true), true);

                    if (microtime(true) - $lastHeartbeat >= self::HEARTBEAT_INTERVAL) {
                        Cache::put(Gateway::MQTT_HEARTBEAT_KEY, time(), now()->addMinutes(10));
                        $lastHeartbeat = microtime(true);
                    }

                    // Kode PHP hanya dimuat sekali saat proses mulai. Jika kode berubah, keluar agar
                    // wrapper (scripts/mqtt-subscribe.bat) menjalankan ulang dengan kode terbaru.
                    if (microtime(true) - $lastCodeCheck >= self::CODE_CHECK_INTERVAL) {
                        $lastCodeCheck = microtime(true);
                        if ($this->codeVersion() !== $codeVersion) {
                            Log::info('MQTT: kode berubah, subscriber dimulai ulang');
                            $this->info('Kode berubah, memulai ulang subscriber...');
                            Cache::forget(Gateway::MQTT_HEARTBEAT_KEY);
                            $client->disconnect();

                            return self::SUCCESS;
                        }
                    }
                }
            } catch (Throwable $e) {
                // Tandai terputus di web segera, tanpa menunggu heartbeat kedaluwarsa
                Cache::forget(Gateway::MQTT_HEARTBEAT_KEY);
                Log::error('MQTT: koneksi terputus, mencoba lagi', ['error' => $e->getMessage()]);
                $this->warn('Koneksi terputus: '.$e->getMessage().'. Mencoba lagi dalam 5 detik...');
                sleep(5);
            }
        }
    }

    /** Sidik jari kode aplikasi: jumlah dan waktu ubah terbaru file di app, config, dan routes. */
    private function codeVersion(): string
    {
        $count = 0;
        $latest = 0;
        foreach (['app', 'config', 'routes'] as $dir) {
            $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(base_path($dir), \FilesystemIterator::SKIP_DOTS));
            foreach ($iterator as $file) {
                $count++;
                $latest = max($latest, $file->getMTime());
            }
        }

        return $count.':'.$latest;
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
