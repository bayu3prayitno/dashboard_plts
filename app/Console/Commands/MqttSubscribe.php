<?php

namespace App\Console\Commands;

use App\Models\Gateway;
use App\Services\GatewayStatusUpdater;
use App\Services\LegacyGatewayPayloadAdapter;
use App\Services\MqttSettings;
use App\Services\TelemetryIngestor;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
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

    /** Jeda reconnect (detik): naik bertahap sampai batas ini selama broker/DB belum bisa dihubungi. */
    private const RETRY_MAX_DELAY = 30;

    public function handle(TelemetryIngestor $ingestor, LegacyGatewayPayloadAdapter $adapter, GatewayStatusUpdater $statusUpdater): int
    {
        $codeVersion = $this->codeVersion();
        $delay = 2;

        // Loop luar: SETIAP kegagalan (broker putus, DB mati, setting berubah) kembali ke sini,
        // membuat klien baru, lalu connect ulang. Tidak ada jalur yang membuat proses berhenti.
        while (true) {
            $client = null;

            try {
                // Dibaca ulang setiap (re)connect dari menu Pengaturan Sistem
                $config = MqttSettings::current();
                $settingsVersion = MqttSettings::fingerprint();

                $settings = (new ConnectionSettings)
                    ->setUsername($config['username'])
                    ->setPassword($config['password'])
                    ->setUseTls($config['tls'])
                    ->setConnectTimeout(5)
                    ->setKeepAliveInterval(15);

                // Client ID tetap diperlukan untuk sesi MQTT persisten. Tanpa PID,
                // broker dapat mengirim ulang pesan QoS 1 yang belum diterima saat reconnect.
                $client = new MqttClient($config['host'], $config['port'], $config['client_id']);
                $client->connect($settings, false);

                $client->subscribe($config['topic'], fn (string $topic, string $message) => $this->handleMessage($topic, $message, $ingestor, $adapter, $statusUpdater), 1);

                Log::info('MQTT: terhubung', ['host' => $config['host'], 'port' => $config['port']]);
                $this->info("Terhubung ke broker {$config['host']}:{$config['port']}, topik {$config['topic']}");
                $delay = 2;

                $lastHeartbeat = 0.0;
                $lastSettingsCheck = microtime(true);
                $lastCodeCheck = microtime(true);

                while (true) {
                    // Error koneksi di sini dilempar ke catch luar → reconnect dengan klien baru
                    $client->loopOnce(microtime(true), true);

                    // Heartbeat hanya ditulis selama loop benar-benar berjalan (= masih terhubung)
                    if (microtime(true) - $lastHeartbeat >= self::HEARTBEAT_INTERVAL) {
                        $this->safeCache(fn () => Cache::put(Gateway::MQTT_HEARTBEAT_KEY, time(), now()->addMinutes(10)));
                        $lastHeartbeat = microtime(true);
                    }

                    // Setting broker berubah: putus dan sambung ulang di dalam proses ini (tanpa restart).
                    if (microtime(true) - $lastSettingsCheck >= self::SETTINGS_CHECK_INTERVAL) {
                        $lastSettingsCheck = microtime(true);
                        if (MqttSettings::fingerprint() !== $settingsVersion) {
                            Log::info('MQTT: setting broker berubah, menyambung ulang');
                            $this->info('Setting MQTT berubah, menyambung ulang...');
                            $this->closeQuietly($client);
                            $delay = 0;

                            continue 2;
                        }
                    }

                    // Kode PHP hanya dimuat sekali saat proses mulai. Jika kode berubah, keluar dengan
                    // kode non-nol agar wrapper (scripts/mqtt-subscribe.bat atau concurrently) menjalankan ulang.
                    if (microtime(true) - $lastCodeCheck >= self::CODE_CHECK_INTERVAL) {
                        $lastCodeCheck = microtime(true);
                        if ($this->codeVersion() !== $codeVersion) {
                            Log::info('MQTT: kode berubah, subscriber dimulai ulang');
                            $this->info('Kode berubah, memulai ulang subscriber...');
                            $this->closeQuietly($client);

                            return self::FAILURE;
                        }
                    }
                }
            } catch (Throwable $e) {
                // Catch ini TIDAK boleh melempar lagi (dulu Cache::forget ke DB yang mati membuat proses crash)
                $this->closeQuietly($client);
                Log::error('MQTT: koneksi terputus, mencoba lagi', ['error' => $e->getMessage(), 'retry_in' => $delay]);
                $this->warn("Koneksi terputus: {$e->getMessage()}. Mencoba lagi dalam {$delay} detik...");

                // DB mungkin ikut putus; paksa koneksi baru pada percobaan berikutnya
                try { DB::purge(); } catch (Throwable) {}

                sleep($delay);
                $delay = min(max($delay * 2, 2), self::RETRY_MAX_DELAY);
            }
        }
    }

    /** Tutup klien & tandai terputus di web tanpa pernah melempar exception. */
    private function closeQuietly(?MqttClient $client): void
    {
        $this->safeCache(fn () => Cache::forget(Gateway::MQTT_HEARTBEAT_KEY));

        try {
            if ($client?->isConnected()) {
                $client->disconnect();
            }
        } catch (Throwable) {
            // Socket sudah mati; abaikan
        }
    }

    /** Operasi cache tidak boleh menjatuhkan subscriber (mis. saat store cache tidak bisa dihubungi). */
    private function safeCache(callable $fn): void
    {
        try {
            $fn();
        } catch (Throwable $e) {
            Log::warning('MQTT: gagal menulis cache', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Sidik jari kode yang dipakai subscriber: command ini + semua Service + semua Model
     * (mis. Alert & AlertEvaluator ikut dimuat saat menyimpan data). Cukup ±20 file, ringan.
     */
    private function codeVersion(): string
    {
        $files = array_merge(
            [base_path('app/Console/Commands/MqttSubscribe.php')],
            glob(base_path('app/Services/*.php')) ?: [],
            glob(base_path('app/Models/*.php')) ?: [],
            glob(base_path('app/Support/*.php')) ?: [],
        );

        $latest = 0;
        foreach ($files as $file) {
            $latest = max($latest, (int) @filemtime($file));
        }

        return count($files).':'.$latest;
    }

    private function handleMessage(string $topic, string $message, TelemetryIngestor $ingestor, LegacyGatewayPayloadAdapter $adapter, GatewayStatusUpdater $statusUpdater): void
    {
        // vpp/kampus/{nama_node}: topik tidak memuat kode gateway, jadi gateway ditentukan dari konfigurasi
        $gatewayCode = MqttSettings::gatewayCode();
        if ($gatewayCode === null) {
            Log::warning('MQTT: gateway tujuan tidak ditentukan (isi setting gateway_code atau sisakan satu gateway)', ['topic' => $topic]);

            return;
        }

        $topicNode = explode('/', $topic)[2] ?? null;

        $payload = json_decode($message, true);
        if (! is_array($payload) || $topicNode === null) {
            Log::warning('MQTT: pesan bukan JSON yang valid', ['topic' => $topic]);

            return;
        }

        // Pesan dari gateway sampai ke server = koneksi internet gateway berfungsi saat ini
        try {
            $statusUpdater->markInternetConnected($gatewayCode);
        } catch (Throwable $e) {
            Log::error('MQTT: gagal memperbarui status internet', ['topic' => $topic, 'error' => $e->getMessage()]);
        }

        try {
            $data = $adapter->translate($gatewayCode, $topicNode, $payload);
            if ($data === null) {
                return; // alasan sudah dicatat di adapter
            }

            $ingestor->ingest($gatewayCode, $data);
        } catch (Throwable $e) {
            // Satu pesan yang gagal tidak boleh menghentikan langganan
            Log::error('MQTT: gagal menyimpan data', ['topic' => $topic, 'error' => $e->getMessage()]);
        }
    }
}
