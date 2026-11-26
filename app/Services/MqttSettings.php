<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;

/**
 * Konfigurasi broker MQTT. Satu-satunya sumber: tabel settings, diatur lewat menu Pengaturan Sistem.
 * Nilai default dipakai bila belum pernah disimpan. Tidak dibaca dari .env.
 */
class MqttSettings
{
    /** Kunci setting => nilai default dan keterangan. */
    public const KEYS = [
        'mqtt_host' => ['default' => '127.0.0.1', 'description' => 'Alamat broker MQTT'],
        'mqtt_port' => ['default' => '1883', 'description' => 'Port broker MQTT'],
        'mqtt_username' => ['default' => '', 'description' => 'Username broker MQTT'],
        'mqtt_password' => ['default' => '', 'description' => 'Password broker MQTT (terenkripsi)'],
        'mqtt_tls' => ['default' => '0', 'description' => 'Gunakan TLS ke broker (1 = ya, 0 = tidak)'],
    ];

    /** Awalan ID klien; lihat clientId() untuk ID lengkapnya. */
    public const CLIENT_ID = 'omahiot-subscriber';

    /**
     * ID klien unik per instalasi: awalan + potongan hash APP_KEY, mis. omahiot-subscriber-3fa9c0d1e2.
     * Di broker publik (broker.emqx.io) ID tetap "omahiot-subscriber" bisa dipakai juga oleh instalasi lain;
     * broker lalu memutus salah satu klien setiap kali yang lain connect (saling tendang).
     * ID tetap sama antar-restart agar sesi persisten (QoS 1) tetap berlaku.
     */
    public static function clientId(): string
    {
        return self::CLIENT_ID.'-'.substr(hash('sha256', (string) config('app.key')), 0, 10);
    }

    /**
     * Pola topik tetap (tidak bisa diubah dari menu): vpp/kampus/{kode_gateway}/node{node_id},
     * mis. vpp/kampus/GW-3fa9c/node1. Kode gateway menentukan gateway, node{node_id} menentukan node.
     */
    public const TOPIC = 'vpp/kampus/+/+';

    /**
     * Nilai efektif untuk subscriber: host, port, username, password (plain atau null), tls, client_id, topic.
     *
     * @return array{host: string, port: int, username: ?string, password: ?string, tls: bool, client_id: string, topic: string}
     */
    public static function current(): array
    {
        $stored = self::storedValues();

        return [
            'host' => $stored['mqtt_host'],
            'port' => (int) $stored['mqtt_port'],
            'username' => $stored['mqtt_username'] !== '' ? $stored['mqtt_username'] : null,
            'password' => self::plainPassword($stored['mqtt_password']),
            'tls' => $stored['mqtt_tls'] === '1',
            'client_id' => self::clientId(),
            'topic' => self::TOPIC,
        ];
    }

    /** Nilai untuk form: sama seperti current(), tetapi password tidak pernah ikut (hanya status tersimpan). */
    public static function forForm(): array
    {
        $stored = self::storedValues();
        $stored['mqtt_password'] = '';

        return $stored;
    }

    /** Sidik jari nilai tersimpan. Subscriber memulai ulang bila nilai ini berubah. */
    public static function fingerprint(): string
    {
        return md5(json_encode(self::storedValues()));
    }

    /** Nilai mentah dari tabel settings (password masih terenkripsi), atau default. */
    public static function storedValues(): array
    {
        $stored = Setting::query()->whereIn('key', array_keys(self::KEYS))->pluck('value', 'key');

        return collect(self::KEYS)
            ->map(fn (array $meta, string $key) => $stored->has($key) ? (string) $stored[$key] : $meta['default'])
            ->all();
    }

    public static function encryptPassword(string $plain): string
    {
        return $plain === '' ? '' : Crypt::encryptString($plain);
    }

    private static function plainPassword(string $stored): ?string
    {
        if ($stored === '') {
            return null;
        }

        try {
            return Crypt::decryptString($stored);
        } catch (DecryptException) {
            // APP_KEY berubah sehingga password tidak bisa dibaca; anggap tanpa password
            return null;
        }
    }
}
