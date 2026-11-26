<?php

namespace App\Console\Commands;

use App\Models\Gateway;
use Illuminate\Console\Command;

/** Menandai internet gateway terputus bila tidak ada pesan dari gateway selama timeout. */
class CheckGatewayInternet extends Command
{
    protected $signature = 'gateways:check-internet';

    protected $description = 'Tandai internet gateway terputus bila tidak ada pesan dari gateway selama timeout';

    public function handle(): int
    {
        $updated = Gateway::query()
            ->where('internet_status', 'connected')
            ->where(function ($query) {
                $query->whereNull('internet_last_seen_at')
                    ->orWhere('internet_last_seen_at', '<', now()->subMinutes(Gateway::connectionTimeoutMinutes()));
            })
            ->update(['internet_status' => 'disconnected']);

        $this->info("Gateway internet terputus: {$updated}");

        return self::SUCCESS;
    }
}
