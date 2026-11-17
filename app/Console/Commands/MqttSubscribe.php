<?php

namespace App\Console\Commands;

use App\Models\Gateway;
use App\Services\GatewayStatusUpdater;
use App\Services\MqttSettings;
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

    /** Seberapa sering (detik) setting MQTT diperiksa. Perubahan diterapkan tanpa restart proses. */
    private const SETTINGS_CHECK_INTERVAL = 5;

    /** Seberapa sering (detik) kode MQTT diperiksa apakah sudah berubah. */
    private const CODE_CHECK_INTERVAL = 300;

    public function handle(TelemetryIngestor $ingestor, GatewayStatusUpdater $statusUpdater): int
    {
        $codeVersion = $this->codeVersion();

        while (true) {
            try {
                // Dibaca ulang setiap (re)connect dari menu Pengaturan Sistem
                $config = MqttSettings::current();
                $settingsVersion = MqttSettings::fingerprint();

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

                $client->subscribe($config['status_topic'], function (string $topic, string $message) use ($statusUpdater) {
                    $this->handleStatus($topic, $message, $statusUpdater);
                }, 0);

                // Loop dengan deteksi disconnect
                $lastHeartbeat = 0.0;
                $lastSettingsCheck = microtime(true);
                $lastCodeCheck = microtime(true);
                $isConnected = true;

                while (true) {
                    try {
                        // Jika disconnect terdeteksi, reconnect
                        if (!$isConnected) {
                            Log::info('MQTT: terdeteksi disconnect, mencoba reconnect...');
                            $this->warn('Terdeteksi disconnect, mencoba reconnect...');
                            $client->disconnect();
                            $client->connect($settings, true);
                            $client->subscribe($config['topic'], function (string $topic, string $message) use ($ingestor) {
                                $this->handleMessage($topic, $message, $ingestor);
                            }, 0);
                            $client->subscribe($config['status_topic'], function (string $topic, string $message) use ($statusUpdater) {
                                $this->handleStatus($topic, $message, $statusUpdater);
                            }, 0);
                            $isConnected = true;
                            $this->info('Reconnect berhasil');
                        }

                        // Gunakan loop dengan timeout 100ms agar responsif
                        $client->loopOnce(100, true);
                        $isConnected = true;
                    } catch (Throwable $e) {
                        Log::warning('MQTT: loopOnce error', ['error' => $e->getMessage()]);
                        $isConnected = false;
                    }

                    if (microtime(true) - $lastHeartbeat >= self::HEARTBEAT_INTERVAL) {
                        Cache::put(Gateway::MQTT_HEARTBEAT_KEY, time(), now()->addMinutes(10));
                        $lastHeartbeat = microtime(true);
                    }

                    // Setting broker berubah: putus dan sambung ulang di dalam proses ini (tanpa restart).
                    if (microtime(true) - $lastSettingsCheck >= self::SETTINGS_CHECK_INTERVAL) {
                        $lastSettingsCheck = microtime(true);
                        if (MqttSettings::fingerprint() !== $settingsVersion) {
                            Log::info('MQTT: setting broker berubah, menyambung ulang');
                            $this->info('Setting MQTT berubah, menyambung ulang...');
                            Cache::forget(Gateway::MQTT_HEARTBEAT_KEY);
                            $client->disconnect();

                            continue 2; // ulangi loop luar: baca setting baru lalu connect
                        }
                    }

                    // Kode PHP hanya dimuat sekali saat proses mulai. Jika kode berubah,
                    // keluar agar wrapper (scripts/mqtt-subscribe.bat) menjalankan ulang dengan kode terbaru.
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

    /** Sidik jari kode MQTT saja: hanya file di app/Console/Commands dan app/Services (bukan seluruh app). */
    private function codeVersion(): string
    {
        $count = 0;
        $latest = 0;
        foreach ([
            base_path('app/Console/Commands/MqttSubscribe.php'),
            base_path('app/Services/MqttSettings.php'),
            base_path('app/Services/TelemetryIngestor.php'),
            base_path('app/Services/GatewayStatusUpdater.php'),
        ] as $file) {
            if (file_exists($file)) {
                $count++;
                $latest = max($latest, filemtime($file));
            }
        }

        return $count.':'.$latest;
    }

    /** omahiot/gateway/{kode_gateway}/status: perbarui status internet gateway. */
    private function handleStatus(string $topic, string $message, GatewayStatusUpdater $statusUpdater): void
    {
        $gatewayCode = explode('/', $topic)[2] ?? null;

        $payload = json_decode($message, true);
        if (! is_array($payload) || $gatewayCode === null) {
            Log::warning('MQTT: pesan status bukan JSON yang valid', ['topic' => $topic]);

            return;
        }

        try {
            $statusUpdater->update($gatewayCode, $payload);
        } catch (Throwable $e) {
            Log::error('MQTT: gagal menyimpan status gateway', ['topic' => $topic, 'error' => $e->getMessage()]);
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
