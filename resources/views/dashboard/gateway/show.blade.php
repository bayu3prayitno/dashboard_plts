@extends('layouts.app')

@use('App\Support\Format')

@section('title', $gateway->name.' - OmahIoT')

@section('content')
    {{-- Judul halaman --}}
    <div class="mb-5 flex flex-wrap items-end justify-between gap-3 px-1">
        <div>
            <h1 class="text-2xl font-semibold sm:text-3xl">{{ $gateway->name }}</h1>
            <p class="mt-1 text-sm text-muted">Status penerima LoRa, koneksi broker MQTT, dan koneksi server.</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('gateway.edit', $gateway) }}" class="btn btn-outline bg-white">
                <x-icon name="pencil" :size="15" /> Ubah identitas
            </a>
            <button type="button" onclick="deleteResource(@js(route('gateway.delete', $gateway)), { title: 'Hapus gateway?', confirmText: @js('Gateway '.$gateway->name.' akan dihapus permanen.'), redirect: @js(route('gateway.index')) })" class="btn btn-outline bg-white text-red-600">Hapus</button>
        </div>
    </div>

    {{-- Status --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <section class="card">
            <div class="flex items-center gap-2 text-sm text-muted"><x-icon name="router" :size="16" /> Gateway</div>
            <div class="mt-3"><x-status-badge :status="$gateway->status" /></div>
            <p class="mt-2 text-xs break-all text-muted">{{ $gateway->gateway_code }}</p>
        </section>
        <section class="card">
            <div class="flex items-center gap-2 text-sm text-muted"><x-icon name="server" :size="16" /> Broker MQTT</div>
            <div class="mt-3"><x-status-badge :status="$gateway->mqtt_status" /></div>
            <p class="mt-2 text-xs text-muted">Koneksi subscriber server ke broker</p>
        </section>
        <section class="card">
            <div class="flex items-center gap-2 text-sm text-muted"><x-icon name="globe" :size="16" /> Koneksi Server</div>
            <div class="mt-3"><x-status-badge :status="$gateway->internet_status" /></div>
            <p class="mt-2 text-xs text-muted">Pesan dari gateway diterima server dalam 15 menit terakhir</p>
        </section>
        <section class="card">
            <div class="flex items-center gap-2 text-sm text-muted"><x-icon name="clock" :size="16" /> Komunikasi terakhir</div>
            <p class="mt-3 text-xl font-semibold">{{ Format::relTime($gateway->last_seen_at) }}</p>
            @if($gateway->last_seen_at)
                <p class="mt-2 text-xs text-muted">{{ $gateway->last_seen_at->timezone(\App\Http\Controllers\DashboardController::tz())->translatedFormat('d M Y, H:i') }} {{ \App\Http\Controllers\DashboardController::tz() }}</p>
            @endif
        </section>
    </div>

    <div class="mt-4 grid grid-cols-1 gap-4 xl:grid-cols-3">
        {{-- Identitas --}}
        <section class="card">
            <div class="mb-4 flex items-center justify-between gap-3">
                <h2 class="text-base font-semibold">Identitas</h2>
            </div>
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between gap-4">
                    <dt class="shrink-0 text-muted">Nama</dt>
                    <dd class="text-right font-medium break-all">{{ $gateway->name }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="shrink-0 text-muted">Kode Gateway</dt>
                    <dd class="text-right font-medium break-all">{{ $gateway->gateway_code }}</dd>
                </div>
                @php($topicPrefix = 'vpp/'.$gateway->gateway_code.'/')
                <div>
                    <dt class="text-muted">Topic MQTT</dt>
                    <dd class="mt-1.5 flex items-center gap-2 rounded bg-canvas/60 p-2.5">
                        <span class="flex-1 font-mono text-xs break-all">{{ $topicPrefix }}<span class="text-muted">node{node_id}</span></span>
                        <button type="button" onclick="copyTopicPrefix(@js($topicPrefix))" class="shrink-0 rounded p-1 text-muted hover:bg-white hover:text-primary" title="Salin {{ $topicPrefix }}" aria-label="Salin topic MQTT">
                            <x-icon name="copy" :size="15" />
                        </button>
                    </dd>
                </div>
            </dl>
        </section>

        {{-- Node terhubung --}}
        <section class="card xl:col-span-2">
            <div class="mb-4 flex items-center justify-between gap-3">
                <h2 class="text-base font-semibold">Node Terhubung</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[560px] text-left text-sm">
                    <thead>
                        <tr class="border-b border-line text-xs text-muted">
                            @foreach (['Node', 'Status', 'RSSI', 'SNR', 'Packet loss', 'Data terakhir'] as $h)
                                <th class="px-3 py-2.5 font-medium whitespace-nowrap">{{ $h }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @forelse ($devices as $item)
                            @php($d = $item['device'])
                            @php($latest = $item['latest'])
                            <tr>
                                <td class="px-3 py-3 align-middle"><a href="{{ route('devices.show', $d) }}" class="font-medium hover:text-primary hover:underline">{{ $d->name }}</a></td>
                                <td class="px-3 py-3 align-middle"><x-status-badge :status="$d->status()" /></td>
                                <td class="px-3 py-3 align-middle tabular-nums">{{ $latest?->rssi !== null ? $latest->rssi.' dBm' : '—' }}</td>
                                <td class="px-3 py-3 align-middle tabular-nums">{{ $latest ? Format::num((float) $latest->snr, 1).' dB' : '—' }}</td>
                                <td class="px-3 py-3 align-middle tabular-nums">{{ $latest ? Format::num((float) $latest->packet_loss, 1).' %' : '—' }}</td>
                                <td class="px-3 py-3 align-middle text-xs whitespace-nowrap text-muted">{{ Format::relTime($d->last_seen_at) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-3 py-4 text-center text-sm text-muted">Belum ada node yang terhubung ke gateway ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>

<script>
function copyTopicPrefix(text) {
    navigator.clipboard.writeText(text)
        .then(() => showToast('Topic disalin: ' + text, 'success'))
        .catch(() => showToast('Gagal menyalin. Salin secara manual.', 'error'));
}
</script>
@endsection

