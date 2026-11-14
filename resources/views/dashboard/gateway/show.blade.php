@extends('layouts.app')

@use('App\Support\Format')

@section('title', $gateway['nama'].' - OmahIoT')

@php
    $tiles = [
        ['icon' => 'router', 'label' => 'Gateway', 'status' => $gateway['status'], 'note' => $gateway['nama']],
        ['icon' => 'server', 'label' => 'MQTT', 'status' => $gateway['mqtt_status'], 'note' => $gateway['broker']],
        ['icon' => 'globe', 'label' => 'Internet', 'status' => $gateway['internet_status'], 'note' => 'Koneksi gateway ke jaringan luar'],
    ];
    $identity = [
        ['Nama', $gateway['nama']],
        ['Lokasi', $gateway['lokasi']],
        ['Interval beacon', $gateway['beacon_menit'].' menit'],
        ['Topic MQTT', $gateway['topic']],
        ['Halaman web lokal', 'http://'.$gateway['ip_lokal']],
    ];
@endphp

@section('content')
    {{-- Judul halaman --}}
    <div class="mb-5 flex flex-wrap items-end justify-between gap-3 px-1">
        <div>
            <h1 class="text-2xl font-semibold sm:text-3xl">Gateway</h1>
            <p class="mt-1 text-sm text-muted">Status penerima LoRa, koneksi MQTT, dan internet.</p>
        </div>
        <button type="button" data-modal-open="gateway-form" class="btn btn-outline bg-white">
            <x-icon name="pencil" :size="15" /> Ubah identitas
        </button>
    </div>

    {{-- Status --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($tiles as $t)
            <section class="card">
                <div class="flex items-center gap-2 text-sm text-muted"><x-icon :name="$t['icon']" :size="16" /> {{ $t['label'] }}</div>
                <div class="mt-3"><x-status-badge :status="$t['status']" /></div>
                <p class="mt-2 text-xs break-all text-muted">{{ $t['note'] }}</p>
            </section>
        @endforeach
        <section class="card">
            <div class="flex items-center gap-2 text-sm text-muted"><x-icon name="clock" :size="16" /> Komunikasi terakhir</div>
            <p class="mt-3 text-xl font-semibold">{{ Format::relTime($gateway['last_seen']) }}</p>
            <p class="mt-2 text-xs text-muted">{{ $gateway['last_seen']->translatedFormat('d M Y, H:i') }} WIB</p>
        </section>
    </div>

    <div class="mt-4 grid grid-cols-1 gap-4 xl:grid-cols-3">
        {{-- Identitas --}}
        <section class="card">
            <div class="mb-4 flex items-center justify-between gap-3">
                <h2 class="text-base font-semibold">Identitas</h2>
            </div>
            <dl class="space-y-3 text-sm">
                @foreach ($identity as [$label, $value])
                    <div class="flex justify-between gap-4">
                        <dt class="shrink-0 text-muted">{{ $label }}</dt>
                        <dd class="text-right font-medium break-all">{{ $value }}</dd>
                    </div>
                @endforeach
            </dl>
            <p class="mt-4 rounded-2xl bg-canvas p-3 text-xs text-muted">
                Gateway juga menyimpan data ke CSV di kartu SD. Jika koneksi ke website terputus, data dapat
                diunduh dari halaman web lokal gateway di jaringan yang sama.
            </p>
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
                            @foreach (['Node', 'Status', 'RSSI', 'SNR', 'Packet loss', 'Paket terakhir'] as $h)
                                <th class="px-3 py-2.5 font-medium whitespace-nowrap">{{ $h }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @foreach ($devices as $d)
                            <tr>
                                <td class="px-3 py-3 align-middle"><a href="{{ route('devices.show', $d['id']) }}" class="font-medium hover:text-primary hover:underline">{{ $d['nama'] }}</a></td>
                                <td class="px-3 py-3 align-middle"><x-status-badge :status="$d['status']" /></td>
                                <td class="px-3 py-3 align-middle tabular-nums">{{ $d['rssi'] }} dBm</td>
                                <td class="px-3 py-3 align-middle tabular-nums">{{ Format::num($d['snr'], 1) }} dB</td>
                                <td class="px-3 py-3 align-middle tabular-nums">{{ Format::num($d['packet_loss'], 1) }} %</td>
                                <td class="px-3 py-3 align-middle text-xs whitespace-nowrap text-muted">{{ Format::relTime($d['last_seen']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    {{-- Modal ubah identitas --}}
    <div id="gateway-form" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 p-4" data-modal>
        <div role="dialog" aria-modal="true" aria-labelledby="gateway-form-title" class="max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-3xl bg-white p-6">
            <div class="mb-5 flex items-center justify-between">
                <h2 id="gateway-form-title" class="text-lg font-semibold">Ubah Gateway</h2>
                <button type="button" data-modal-close aria-label="Tutup" class="rounded-full p-1.5 text-muted hover:bg-canvas">
                    <x-icon name="x" :size="18" />
                </button>
            </div>
            {{-- TODO: arahkan ke route update gateway saat backend tersedia --}}
            <form class="space-y-4" onsubmit="event.preventDefault(); alert('Penyimpanan identitas gateway akan aktif setelah backend tersedia.');">
                <label class="block text-sm">
                    <span class="mb-1.5 block font-medium">Nama gateway</span>
                    <input name="nama" value="{{ $gateway['nama'] }}" required
                        class="w-full rounded-xl border border-line bg-white px-3 py-2.5 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/15">
                </label>
                <label class="block text-sm">
                    <span class="mb-1.5 block font-medium">Lokasi</span>
                    <input name="lokasi" value="{{ $gateway['lokasi'] }}" required
                        class="w-full rounded-xl border border-line bg-white px-3 py-2.5 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/15">
                </label>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" data-modal-close class="btn text-muted hover:bg-canvas hover:text-ink">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
(function () {
    const modal = document.getElementById('gateway-form');
    const open = () => { modal.classList.remove('hidden'); modal.classList.add('flex'); modal.querySelector('input')?.focus(); };
    const close = () => { modal.classList.add('hidden'); modal.classList.remove('flex'); };
    document.querySelector('[data-modal-open="gateway-form"]').addEventListener('click', open);
    modal.querySelectorAll('[data-modal-close]').forEach((b) => b.addEventListener('click', close));
    modal.addEventListener('mousedown', (e) => { if (e.target === modal) close(); });
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') close(); });
})();
</script>
@endpush
