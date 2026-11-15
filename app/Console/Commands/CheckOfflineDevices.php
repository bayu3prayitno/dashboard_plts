<?php

namespace App\Console\Commands;

use App\Services\AlertEvaluator;
use Illuminate\Console\Command;

/** Membuat alert "Device Offline" untuk perangkat yang tidak mengirim data melewati timeout. */
class CheckOfflineDevices extends Command
{
    protected $signature = 'alerts:check-offline';

    protected $description = 'Buat alert untuk perangkat yang melewati batas waktu offline';

    public function handle(AlertEvaluator $alerts): int
    {
        $created = $alerts->checkOffline(now());

        $this->info("Alert offline baru: {$created}");

        return self::SUCCESS;
    }
}
