<?php

namespace App\Console\Commands;

use App\Services\ConnectionStatus;
use Illuminate\Console\Command;

/** Perbarui kolom status boolean node, gateway, dan MQTT (online → offline bila melewati timeout). */
class RefreshConnectionStatus extends Command
{
    protected $signature = 'status:refresh';

    protected $description = 'Perbarui status online node, koneksi gateway, dan koneksi MQTT di database';

    public function handle(ConnectionStatus $status): int
    {
        $status->refresh();

        return self::SUCCESS;
    }
}
