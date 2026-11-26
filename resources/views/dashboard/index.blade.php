@extends('layouts.app')

@section('title', 'OmahIoT Dashboard')

@use('App\Support\Format')

@php
    // Sparkline irradiance 24 jam
    $sparkPoints = array_values(array_filter($irradianceSpark, fn ($p) => $p['value'] !== null));
    $sparkPath = '';
    if (count($sparkPoints) > 1) {
        $vals = array_column($sparkPoints, 'value');
        $min = min($vals);
        $span = (max($vals) - $min) ?: 1;
        foreach ($sparkPoints as $i => $p) {
            $x = $i / (count($sparkPoints) - 1) * 100;
            $y = 26 - ($p['value'] - $min) / $span * 24;
            $sparkPath .= ($i ? 'L' : 'M').number_format($x, 1, '.', '').' '.number_format($y, 1, '.', '').' ';
        }
    }

    // Gauge setengah lingkaran
    $gaugeLen = M_PI * 80;
    $gaugeFill = $received === null ? 0 : $gaugeLen * min(100, max(0, $received)) / 100;

    $tiles = [
        ['route' => 'devices.index', 'icon' => 'cpu', 'label' => 'Total perangkat', 'value' => count($devices)],
        ['route' => 'devices.index', 'icon' => 'wifi', 'label' => 'Online', 'value' => $onlineCount],
        ['route' => 'devices.index', 'icon' => 'wifi-off', 'label' => "Offline > {$offlineMinutes} mnt", 'value' => $offlineCount],
        ['route' => 'alerts.index', 'icon' => 'bell-ring', 'label' => 'Peringatan aktif', 'value' => $activeAlertCount],
    ];
@endphp

@section('content')
    {{-- ================= HERO ================= --}}
    {{-- Margin negatif membuat header (cari + notifikasi) menumpang di atas banner --}}
    {{-- Ponsel (< md): full-bleed, menembus padding layout (p-3) ke tepi kiri/kanan/atas layar --}}
    <section data-hero class="hero-surface relative -mt-16 mb-4 overflow-hidden rounded-3xl px-5 pt-20 pb-5 sm:px-7 md:min-h-[25rem]
                              max-md:-mx-3 max-md:-mt-[4.75rem] max-md:rounded-t-none max-md:pt-24 max-md:pb-6">
        {{-- Langit: matahari/bulan mengikuti jam (komponen dipakai juga di halaman login) --}}
        <x-hero-sky :peak-clear="38" />

        {{-- WebP 1000px (134 KB) menggantikan PNG 1,6 MB; fetchpriority karena ini elemen terbesar di layar --}}
        <img data-hero-img src="{{ asset('images/plts-3D.webp') }}" alt="" width="1000" height="1000" fetchpriority="high" decoding="async"
            class="pointer-events-none relative mx-auto -mb-2 h-64 w-auto object-contain sm:h-72 drop-shadow-xl select-none md:absolute md:top-1/2 md:left-[36%] md:mb-0 md:h-[112%] md:-translate-y-[41%] xl:left-[40%]">

        <div class="relative flex h-full flex-col gap-6 md:min-h-[19rem] md:justify-between">
            <div class="max-w-sm max-md:mx-auto max-md:text-center">
                <h1 data-hero-title class="text-3xl leading-tight font-medium tracking-tight uppercase transition-colors duration-1000 sm:text-4xl">Dashboard<br>Monitoring IoT</h1>
                <p data-hero-subtitle class="mt-2 text-sm text-muted transition-colors duration-1000">Pantau node sensor dan gateway LoRa secara real-time.</p>
                <div class="mt-4 flex flex-wrap gap-2 max-md:justify-center">
                    <a href="{{ route('devices.create') }}" class="btn btn-primary hidden"><x-icon name="plus" :size="16" /> Tambah Perangkat</a>
                    <a href="{{ route('historical.exportCsv') }}" class="btn btn-outline bg-white/60 hidden"><x-icon name="download" :size="16" /> Ekspor CSV</a>
                </div>
            </div>

            <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <div class="flex flex-col gap-3 sm:flex-row">
                    {{-- Daya saat ini --}}
                    <div class="glass w-full rounded-2xl p-4 sm:w-48">
                        <div class="flex items-start justify-between gap-2">
                            <p class="text-xs leading-tight font-medium">Daya saat ini</p>
                            <a href="{{ route('historical.index') }}" aria-label="Lihat histori Daya" class="grid size-7 shrink-0 place-items-center rounded-full bg-white shadow-sm">
                                <x-icon name="arrow-up-right" :size="14" />
                            </a>
                        </div>
                        <p class="mt-1 text-2xl font-semibold tabular-nums">{{ Format::num($powerNow, 1) }} <span class="text-xs font-medium text-muted">W</span></p>
                        <div class="mt-2 h-7">
                            <div class="mt-2 h-2.5 overflow-hidden rounded-full bg-primary" role="img"
                                title="{{ round($powerShare) }}% dari puncak 24 jam terakhir" aria-label="{{ round($powerShare) }}% dari puncak 24 jam terakhir">
                                <div class="h-full rounded-full bg-lime" style="width: {{ $powerShare }}%"></div>
                            </div>
                        </div>
                    </div>
                    {{-- Irradiance --}}
                    <div class="glass w-full rounded-2xl p-4 sm:w-48">
                        <div class="flex items-start justify-between gap-2">
                            <p class="text-xs leading-tight font-medium">Irradiance</p>
                            <a href="{{ route('historical.index') }}" aria-label="Lihat histori Irradiance" class="grid size-7 shrink-0 place-items-center rounded-full bg-white shadow-sm">
                                <x-icon name="arrow-up-right" :size="14" />
                            </a>
                        </div>
                        <p class="mt-1 text-2xl font-semibold tabular-nums">{{ Format::num($irradianceNow, 0) }} <span class="text-xs font-medium text-muted">W/m²</span></p>
                        <div class="mt-2 h-7">
                            @if ($sparkPath)
                                <svg viewBox="0 0 100 28" preserveAspectRatio="none" class="h-full w-full" aria-hidden="true">
                                    <path d="{{ trim($sparkPath) }}" fill="none" stroke="#6f9f12" stroke-width="2" vector-effect="non-scaling-stroke" stroke-linejoin="round" />
                                </svg>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="glass flex gap-6 rounded-2xl px-5 py-4">
                    <div>
                        <p class="text-2xl font-semibold tabular-nums">{{ $onlineCount }}<span class="text-sm font-medium text-muted">/{{ count($devices) }}</span></p>
                        <p class="text-[11px] font-medium tracking-wide uppercase">Node online</p>
                    </div>
                    <div>
                        <p class="text-2xl font-semibold tabular-nums">{{ Format::num($received, 1) }}<span class="text-sm font-medium text-muted">%</span></p>
                        <p class="text-[11px] font-medium tracking-wide uppercase">Paket diterima</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="grid grid-cols-1 gap-4 xl:grid-cols-12">
        {{-- ================= KOLOM KIRI ================= --}}
        <div class="min-w-0 space-y-4 xl:col-span-7">
            <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
                @foreach ($tiles as $t)
                    <a href="{{ route($t['route']) }}" class="flex items-center gap-3 rounded-3xl bg-white p-4 transition-shadow hover:shadow-md">
                        <span class="grid size-11 shrink-0 place-items-center rounded-full bg-canvas text-muted"><x-icon :name="$t['icon']" :size="19" /></span>
                        <span class="min-w-0">
                            <span class="block text-2xl leading-tight font-semibold tabular-nums">{{ $t['value'] }}</span>
                            <span class="block text-xs leading-tight text-muted">{{ $t['label'] }}</span>
                        </span>
                    </a>
                @endforeach
            </div>

            {{-- Tren sensor --}}
            <section class="card" id="trend-card">
                <div class="mb-4 flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 class="text-xl font-medium">Tren <span data-trend-label>Daya</span></h2>
                        <p class="mt-1 text-xs text-muted" data-trend-sub>Rata-rata harian per perangkat, dalam W.</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <select data-trend-sensor aria-label="Pilih sensor"
                            class="w-auto rounded-full border border-line bg-white px-3 py-1.5 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/15">
                            @foreach ($sensors as $key => $s)
                                <option value="{{ $key }}" @selected($key === 'power')>{{ $s['label'] }}</option>
                            @endforeach
                        </select>
                        <div class="inline-flex max-w-full flex-wrap gap-1 rounded-2xl bg-canvas p-1" role="tablist">
                            <button type="button" data-trend-range="24h" class="rounded-xl px-3 py-1.5 text-sm font-medium text-muted transition-colors hover:text-ink">24 jam</button>
                            <button type="button" data-trend-range="7d" class="rounded-xl bg-primary px-3 py-1.5 text-sm font-medium text-white shadow-sm transition-colors">7 hari</button>
                        </div>
                    </div>
                </div>
                <div class="relative h-[280px]" data-trend-chart>
                    <div data-trend-tip class="pointer-events-none absolute z-10 hidden rounded-xl border border-line bg-white px-3 py-2 text-xs shadow-lg"></div>
                </div>
                <div data-trend-legend class="mt-3 flex flex-wrap gap-x-4 gap-y-1 text-xs text-muted"></div>
            </section>

            {{-- Nilai sensor terkini per node --}}
            <section class="card">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <h2 class="text-base font-semibold">Nilai Sensor Terkini per Node</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[560px] text-left text-sm">
                        <thead>
                            <tr class="border-b border-line text-xs text-muted">
                                <th class="px-3 py-2.5 font-medium whitespace-nowrap">Node</th>
                                @foreach ($sensors as $s)
                                    <th class="px-3 py-2.5 font-medium whitespace-nowrap">{{ $s['label'] }} ({{ $s['unit'] }})</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            @foreach ($devices as $d)
                                <tr>
                                    <td class="px-3 py-3 align-middle">
                                        <a href="{{ route('devices.show', $d['id']) }}" class="font-medium hover:underline">{{ $d['nama'] }}</a>
                                        <div class="mt-1 flex flex-wrap items-center gap-2">
                                            <x-status-badge :status="$d['status']" />
                                            <span class="text-xs whitespace-nowrap text-muted" title="{{ $d['last_seen'] ? $d['last_seen']->timezone(\App\Http\Controllers\DashboardController::tz())->translatedFormat('d M Y H:i').' WIB' : 'Belum pernah mengirim data' }}">{{ Format::relTime($d['last_seen']) }}</span>
                                        </div>
                                    </td>
                                    @foreach ($sensors as $key => $s)
                                        <td class="px-3 py-3 align-middle font-medium tabular-nums">{{ Format::num($d['latest'][$key], $s['digits']) }}</td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        </div>

        {{-- ================= KOLOM KANAN ================= --}}
        <div class="grid min-w-0 grid-cols-1 content-start gap-4 sm:grid-cols-2 xl:col-span-5">
            {{-- Kualitas link LoRa --}}
            <section class="card">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <h2 class="text-base font-semibold">Kualitas Link LoRa</h2>
                </div>
                <svg viewBox="0 0 200 116" class="mx-auto w-full max-w-64" role="img" aria-label="paket diterima: {{ $received === null ? 'tidak ada data' : round($received).'%' }}">
                    <path d="M20 100 A80 80 0 0 1 180 100" fill="none" stroke="#e7eae8" stroke-width="20" stroke-linecap="round" />
                    @if ($received !== null)
                        <path d="M20 100 A80 80 0 0 1 180 100" fill="none" stroke="#a8d93a" stroke-width="20" stroke-linecap="round" stroke-dasharray="{{ $gaugeFill }} {{ $gaugeLen }}" />
                    @endif
                    <text x="100" y="88" text-anchor="middle" class="fill-ink text-[34px] font-semibold">{{ $received === null ? '—' : round($received).'%' }}</text>
                    <text x="100" y="108" text-anchor="middle" class="fill-muted text-[11px]">paket diterima</text>
                </svg>
                <p class="mt-2 text-center text-xs text-muted">Rata-rata node aktif, 24 jam terakhir</p>
            </section>

            {{-- Status gateway --}}
            <section data-gateway-card class="relative rounded-3xl bg-primary p-5 text-white">
                <div class="mb-4 flex items-center justify-between gap-2">
                    <h1 class="truncate text-base font-semibold">{{ $gateway['nama'] }}</h1>
                    @if (count($gateways) > 1)
                        {{-- Lebih dari satu gateway: panah bawah membuka daftar gateway; memilih gateway memuat ulang seluruh dashboard untuk gateway itu --}}
                        <button type="button" data-gateway-toggle aria-haspopup="menu" aria-expanded="false" aria-controls="gateway-menu" aria-label="Pilih gateway lain"
                            class="grid size-8 shrink-0 place-items-center rounded-full bg-white/10 hover:bg-white/20 [&>svg]:transition-transform [&>svg]:duration-200 aria-expanded:[&>svg]:rotate-180 motion-reduce:[&>svg]:transition-none">
                            <x-icon name="chevron-down" :size="16" />
                        </button>
                    @else
                        <a href="{{ route('gateway.index') }}" aria-label="Buka halaman gateway" class="grid size-8 shrink-0 place-items-center rounded-full bg-white/10 hover:bg-white/20">
                            <x-icon name="arrow-up-right" :size="16" />
                        </a>
                    @endif
                </div>

                @if (count($gateways) > 1)
                    <div id="gateway-menu" data-gateway-menu data-state="closed" role="menu" aria-label="Daftar gateway"
                        class="absolute top-14 right-5 z-20 w-60 origin-top-right rounded-2xl bg-white p-1.5 text-ink shadow-xl ring-1 ring-black/5
                               transition-all transition-discrete duration-150 ease-out motion-reduce:transition-none
                               opacity-100 scale-100 translate-y-0 starting:opacity-0 starting:scale-95 starting:-translate-y-1
                               data-[state=closed]:hidden data-[state=closed]:opacity-0 data-[state=closed]:scale-95 data-[state=closed]:-translate-y-1">
                        @foreach ($gateways as $gw)
                            @php $current = $gw['id'] === $gateway['id']; @endphp
                            <a href="{{ route('dashboard.index', ['gateway' => $gw['id']]) }}" role="menuitem" data-gateway-option
                                @if ($current) aria-current="true" @endif
                                class="group flex w-full items-center gap-2.5 rounded-xl px-3 py-2 text-left text-sm hover:bg-canvas aria-[current=true]:font-semibold">
                                <span class="size-2 shrink-0 rounded-full {{ $gw['internet_status'] === 'connected' ? 'bg-lime' : 'bg-red-400' }}"></span>
                                <span class="min-w-0 flex-1 truncate">{{ $gw['nama'] }}</span>
                                <span class="invisible text-primary group-aria-[current=true]:visible"><x-icon name="check" :size="15" /></span>
                            </a>
                        @endforeach
                        <a href="{{ route('gateway.index') }}" class="mt-1 flex items-center justify-between gap-2 rounded-xl border-t border-line px-3 py-2 text-sm text-muted hover:bg-canvas hover:text-ink">
                            Kelola gateway <x-icon name="arrow-up-right" :size="14" />
                        </a>
                    </div>
                @endif

                <p class="text-sm text-white/60">Status gateway</p>
                <p class="mt-1 flex items-center gap-2 text-3xl font-semibold">
                    {{-- Online bila ada paket dari node mana pun dalam Offline Timeout (lihat Gateway::internetStatus) --}}
                    <span class="size-2.5 rounded-full {{ $gateway['internet_status'] === 'connected' ? 'bg-lime' : 'bg-red-400' }}"></span>
                    {{ $gateway['internet_status'] === 'connected' ? 'Online' : 'Offline' }}
                </p>
                <ul class="mt-4 space-y-2 text-sm">
                    @foreach ([
                        ['server', 'Broker MQTT', $gateway['mqtt_status'] === 'connected' ? 'Terhubung' : 'Terputus'],
                        ['radio', 'Data Terakhir', Format::relTime($gateway['last_seen'])],
                    ] as [$icon, $label, $value])
                        <li class="flex items-center justify-between gap-2">
                            <span class="flex items-center gap-2 text-white/60"><x-icon :name="$icon" :size="15" /> {{ $label }}</span>
                            <span class="font-medium">{{ $value }}</span>
                        </li>
                    @endforeach
                </ul>
            </section>

            {{-- Peringatan terbaru --}}
            <section class="card sm:col-span-2">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <h2 class="text-base font-semibold">Peringatan Terbaru</h2>
                    <a href="{{ route('alerts.index') }}" class="rounded-full border border-line px-3 py-1 text-xs font-medium hover:bg-canvas">Lihat semua</a>
                </div>
                @if ($activeAlertCount === 0)
                    <p class="py-8 text-center text-sm text-muted">Tidak ada peringatan aktif.</p>
                @endif
                <ul class="space-y-3">
                    @foreach (array_slice($activeAlerts, 0, 3) as $a)
                        <li class="flex flex-wrap items-center gap-x-4 gap-y-2 rounded-2xl bg-canvas p-3">
                            <div class="min-w-0 flex-1 basis-48">
                                <p class="flex flex-wrap items-center gap-2 text-sm font-semibold">{{ $a['device'] }} <x-status-badge :status="$a['status']" /></p>
                                <p class="mt-1 text-xs text-muted">{{ $a['text'] }} · {{ Format::relTime($a['waktu']) }}</p>
                            </div>
                            <a href="{{ route('alerts.index') }}" class="btn btn-primary btn-sm">{{ $a['status'] === 'baru' ? 'Tangani' : 'Selesaikan' }}</a>
                        </li>
                    @endforeach
                </ul>
            </section>

            {{-- Perangkat --}}
            <section class="card sm:col-span-2">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <div>
                        <h2 class="text-base font-semibold">Kualitas LoRa per Node</h2>
                        <p class="mt-0.5 text-xs text-muted">Sinyal dari paket terakhir · paket diterima 24 jam terakhir</p>
                    </div>
                    <a href="{{ route('devices.index') }}" class="rounded-full border border-line px-3 py-1 text-xs font-medium hover:bg-canvas">Kelola</a>
                </div>
                @if ($devices === [])
                    <p class="py-6 text-center text-sm text-muted">Belum ada node di gateway ini.</p>
                @endif
                <ul class="space-y-1">
                    @foreach ($devices as $d)
                        @php $link = $d['link']; @endphp
                        <li>
                            <a href="{{ route('devices.show', $d['id']) }}" class="flex items-center gap-3 rounded-2xl p-2 hover:bg-canvas">
                                <span class="grid size-9 shrink-0 place-items-center rounded-xl bg-primary-soft text-sm font-semibold">{{ $d['node_id'] }}</span>
                                <span class="min-w-0 flex-1">
                                    <span class="flex items-center gap-2">
                                        <span class="truncate text-sm font-medium">{{ $d['nama'] }}</span>
                                        @if ($d['quality'])
                                            <x-status-badge :status="$d['quality']" class="py-0.5" />
                                        @endif
                                    </span>
                                    <span class="block truncate text-xs text-muted tabular-nums">
                                        @if ($d['rssi'] !== null || $d['snr'] !== null)
                                            RSSI {{ $d['rssi'] ?? '—' }} dBm · SNR {{ Format::num($d['snr'], 1) }} dB
                                        @else
                                            Belum ada data sinyal
                                        @endif
                                    </span>
                                </span>
                                <span class="w-24 shrink-0 text-right">
                                    @if ($link['percent'] !== null)
                                        <span class="block text-sm font-semibold tabular-nums">{{ Format::num($link['percent'], 0) }}%</span>
                                        <span class="block text-[11px] text-muted tabular-nums" title="Paket diterima / seharusnya (1 paket per interval beacon)">{{ $link['received'] }}/{{ $link['expected'] }} paket</span>
                                        <span class="mt-1 block h-1.5 overflow-hidden rounded-full bg-line">
                                            <span class="block h-full rounded-full {{ $link['percent'] >= 90 ? 'bg-lime' : ($link['percent'] >= 70 ? 'bg-amber-400' : 'bg-red-400') }}" style="width: {{ $link['percent'] }}%"></span>
                                        </span>
                                    @else
                                        <span class="text-xs text-muted">—</span>
                                    @endif
                                </span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </section>
        </div>
    </div>
@endsection

@push('scripts')
<script>
// Kartu status gateway: buka/tutup daftar gateway (hanya ada bila gateway > 1).
// Tiap item adalah tautan ?gateway=ID, jadi seluruh dashboard dimuat ulang untuk gateway itu.
(function () {
    const card = document.querySelector('[data-gateway-card]');
    const toggle = card?.querySelector('[data-gateway-toggle]');
    if (!toggle) return;

    const menu = card.querySelector('[data-gateway-menu]');
    const options = [...menu.querySelectorAll('[data-gateway-option]')];

    const isOpen = () => menu.dataset.state === 'open';
    const setOpen = (open) => {
        menu.dataset.state = open ? 'open' : 'closed';
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    };

    toggle.addEventListener('click', () => {
        setOpen(!isOpen());
        if (isOpen()) (options.find((o) => o.getAttribute('aria-current') === 'true') ?? options[0]).focus();
    });
    document.addEventListener('click', (e) => {
        if (isOpen() && !menu.contains(e.target) && !toggle.contains(e.target)) setOpen(false);
    });
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && isOpen()) {
            setOpen(false);
            toggle.focus();
        }
    });
})();

(function () {
    const TRENDS = @json($trendDevices);
    const SENSORS = @json($sensors);
    const card = document.getElementById('trend-card');
    const box = card.querySelector('[data-trend-chart]');
    const tip = card.querySelector('[data-trend-tip]');
    const legend = card.querySelector('[data-trend-legend]');
    const select = card.querySelector('[data-trend-sensor]');
    const tabs = card.querySelectorAll('[data-trend-range]');
    const NS = 'http://www.w3.org/2000/svg';
    const C = { axis: '#6f766f', grid: '#eceeea', cursor: '#b9c2bd', hover: '#f4f5f4' };
    // Warna per perangkat (urut sesuai daftar perangkat aktif)
    const PALETTE = ['#6f9f12', '#2563eb', '#ea580c', '#8b5cf6', '#0891b2', '#db2777', '#ca8a04', '#16a34a', '#64748b', '#dc2626'];
    const colorOf = (i) => PALETTE[i % PALETTE.length];
    let sensor = 'power', range = '7d';

    const fmt = (v, d) => v == null ? '—' : v.toLocaleString('id-ID', { minimumFractionDigits: d, maximumFractionDigits: d });
    const esc = (t) => String(t).replace(/[&<>"]/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' })[c]);
    const el = (tag, attrs, parent) => {
        const n = document.createElementNS(NS, tag);
        for (const k in attrs) n.setAttribute(k, attrs[k]);
        parent && parent.appendChild(n);
        return n;
    };
    // Tick sumbu-Y yang "rapi"
    const niceTicks = (min, max, count = 5) => {
        if (min === max) { min -= 1; max += 1; }
        const raw = (max - min) / (count - 1);
        const mag = 10 ** Math.floor(Math.log10(raw));
        const step = [1, 2, 2.5, 5, 10].map((m) => m * mag).find((s) => s >= raw);
        const lo = Math.floor(min / step) * step, hi = Math.ceil(max / step) * step;
        const ticks = [];
        for (let v = lo; v <= hi + step / 2; v += step) ticks.push(+v.toFixed(6));
        return ticks;
    };

    /**
     * Kurva halus melalui semua titik (interpolasi monotone cubic, seperti d3.curveMonotoneX):
     * tidak menyiku, dan tidak melampaui nilai data (kurva tidak turun di bawah minimum / naik di atas maksimum).
     * pts: [[x, y], ...] urut menurut x.
     */
    function smoothPath(pts) {
        const n = pts.length;
        if (n < 3) return pts.map(([px, py], k) => `${k ? 'L' : 'M'}${px} ${py}`).join('');

        const sign = (v) => (v < 0 ? -1 : v > 0 ? 1 : 0);
        const h = [], s = [];
        for (let k = 0; k < n - 1; k++) {
            h[k] = pts[k + 1][0] - pts[k][0];
            s[k] = h[k] ? (pts[k + 1][1] - pts[k][1]) / h[k] : 0;
        }
        // Kemiringan di titik tengah: 0 bila arah berbalik (puncak/lembah), selain itu dibatasi agar tetap monoton
        const t = [];
        for (let k = 1; k < n - 1; k++) {
            const p = (s[k - 1] * h[k] + s[k] * h[k - 1]) / (h[k - 1] + h[k]);
            t[k] = (sign(s[k - 1]) + sign(s[k])) * Math.min(Math.abs(s[k - 1]), Math.abs(s[k]), 0.5 * Math.abs(p)) || 0;
        }
        t[0] = (3 * s[0] - t[1]) / 2;
        t[n - 1] = (3 * s[n - 2] - t[n - 2]) / 2;

        let d = `M${pts[0][0]} ${pts[0][1]}`;
        for (let k = 0; k < n - 1; k++) {
            const [x0, y0] = pts[k], [x1, y1] = pts[k + 1], dx = h[k] / 3;
            d += `C${x0 + dx} ${y0 + t[k] * dx} ${x1 - dx} ${y1 - t[k + 1] * dx} ${x1} ${y1}`;
        }
        return d;
    }

    function draw() {
        const meta = SENSORS[sensor];
        const data = TRENDS[sensor][range]; // { labels, titles, series: [{ name, data }] }
        const n = data.labels.length;
        const isBar = range === '7d';
        card.querySelector('[data-trend-label]').textContent = meta.label;
        card.querySelector('[data-trend-sub]').textContent =
            `${isBar ? 'Rata-rata harian' : 'Rata-rata per 30 menit'} per perangkat, dalam ${meta.unit}.`;
        tabs.forEach((t) => {
            const on = t.dataset.trendRange === range;
            t.className = 'rounded-xl px-3 py-1.5 text-sm font-medium transition-colors ' +
                (on ? 'bg-primary text-white shadow-sm' : 'text-muted hover:text-ink');
        });

        box.querySelector('svg')?.remove();
        box.querySelector('[data-empty]')?.remove();
        legend.innerHTML = '';

        const all = data.series.flatMap((s) => s.data.filter((v) => v != null));
        if (!all.length) {
            box.insertAdjacentHTML('beforeend', '<p data-empty class="py-8 text-center text-sm text-muted">Belum ada data pada rentang ini.</p>');
            return;
        }

        const W = box.clientWidth, H = box.clientHeight;
        const m = { t: 8, r: 8, b: 26, l: 48 };
        const iw = W - m.l - m.r, ih = H - m.t - m.b;
        // Batang mulai dari 0; garis boleh mengikuti rentang data
        const ticks = niceTicks(isBar ? 0 : Math.min(...all), Math.max(...all));
        const y0 = ticks[0], y1 = ticks.at(-1);
        const y = (v) => m.t + ih - ((v - y0) / (y1 - y0)) * ih;
        const slot = iw / n;
        const x = (i) => m.l + (n > 1 ? i / (n - 1) : 0.5) * iw; // posisi titik (mode garis)
        const center = (i) => (isBar ? m.l + slot * (i + 0.5) : x(i));
        const svg = el('svg', { width: W, height: H, class: 'block' });
        box.prepend(svg);

        ticks.forEach((t) => {
            el('line', { x1: m.l, x2: W - m.r, y1: y(t), y2: y(t), stroke: C.grid }, svg);
            const tx = el('text', { x: m.l - 8, y: y(t) + 4, 'text-anchor': 'end', 'font-size': 11, fill: C.axis }, svg);
            tx.textContent = fmt(t, t % 1 ? 1 : 0);
        });

        // Label sumbu-X
        const step = isBar ? 1 : Math.max(1, Math.ceil(n / 12));
        data.labels.forEach((lbl, i) => {
            if (i % step !== 0 && i !== n - 1) return;
            const t = el('text', { x: center(i), y: H - 6, 'text-anchor': 'middle', 'font-size': 11, fill: C.axis }, svg);
            t.textContent = lbl;
        });

        // Sorot kolom/titik yang sedang disorot kursor (di belakang batang)
        const hl = isBar ? el('rect', { x: m.l, y: m.t, width: slot, height: ih, fill: 'transparent', 'pointer-events': 'none' }, svg) : null;

        if (isBar) {
            // Batang berkelompok: satu batang per perangkat dalam setiap hari
            const S = data.series.length;
            // Lebar kelompok mengikuti ruang per hari (maks. 40px per batang)
            const groupW = Math.min(slot * 0.8, S * 40);
            const bw = groupW / S;
            const base = y(y0);
            data.series.forEach((s, si) => {
                s.data.forEach((v, i) => {
                    if (v == null) return;
                    const cx = m.l + slot * (i + 0.5) - groupW / 2 + bw * (si + 0.5);
                    const top = y(v);
                    const w = Math.max(bw - 2, 2);
                    const r = Math.min(4, w / 2, Math.max(base - top, 0));
                    const x0 = cx - w / 2;
                    el('path', {
                        d: `M${x0} ${base}V${top + r}Q${x0} ${top} ${x0 + r} ${top}H${x0 + w - r}Q${x0 + w} ${top} ${x0 + w} ${top + r}V${base}Z`,
                        fill: colorOf(si), 'pointer-events': 'none',
                    }, svg);
                });
            });
        } else {
            // Satu kurva halus per perangkat + arsiran gradasi tipis di bawahnya; titik tetap digambar agar data yang jarang terlihat
            const defs = el('defs', {}, svg);
            const base = y(y0);
            data.series.forEach((s, si) => {
                const color = colorOf(si);
                const pts = s.data.map((v, i) => (v == null ? null : [x(i), y(v)])).filter(Boolean);
                if (pts.length > 1) {
                    const line = smoothPath(pts);
                    const gid = `trend-fill-${si}`;
                    const grad = el('linearGradient', { id: gid, x1: 0, y1: 0, x2: 0, y2: 1 }, defs);
                    el('stop', { offset: '0%', 'stop-color': color, 'stop-opacity': data.series.length > 1 ? 0.10 : 0.18 }, grad);
                    el('stop', { offset: '100%', 'stop-color': color, 'stop-opacity': 0 }, grad);
                    el('path', { d: `${line}L${pts.at(-1)[0]} ${base}L${pts[0][0]} ${base}Z`, fill: `url(#${gid})`, 'pointer-events': 'none' }, svg);
                    el('path', { d: line, fill: 'none', stroke: color, 'stroke-width': 2.5, 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'pointer-events': 'none' }, svg);
                }
                pts.forEach(([px, py]) => el('circle', { cx: px, cy: py, r: 3, fill: '#fff', stroke: color, 'stroke-width': 1.5, 'pointer-events': 'none' }, svg));
            });
        }

        data.series.forEach((s, si) => {
            const item = document.createElement('span');
            item.className = 'inline-flex items-center gap-1.5';
            item.innerHTML = `<span class="h-2.5 w-2.5 rounded-full" style="background:${colorOf(si)}"></span>${esc(s.name)}`;
            legend.appendChild(item);
        });

        // Kursor dan titik per perangkat (mode garis)
        const cursor = isBar ? null : el('line', { y1: m.t, y2: m.t + ih, stroke: C.cursor, visibility: 'hidden' }, svg);
        const dots = isBar ? [] : data.series.map((s, si) => el('circle', { r: 4, fill: colorOf(si), stroke: '#fff', 'stroke-width': 2, visibility: 'hidden' }, svg));

        const hide = () => {
            tip.classList.add('hidden');
            if (hl) hl.setAttribute('fill', 'transparent');
            if (cursor) cursor.setAttribute('visibility', 'hidden');
            dots.forEach((d) => d.setAttribute('visibility', 'hidden'));
        };

        // Hover: tooltip berisi semua perangkat pada titik/hari yang ditunjuk
        const overlay = el('rect', { x: m.l, y: m.t, width: iw, height: ih, fill: 'transparent' }, svg);
        overlay.addEventListener('mousemove', (e) => {
            const px = e.clientX - svg.getBoundingClientRect().left;
            const i = isBar
                ? Math.floor((px - m.l) / slot)
                : Math.round(((px - m.l) / iw) * (n - 1));
            const idx = Math.max(0, Math.min(n - 1, i));
            const rows = data.series
                .map((s, si) => ({ s, si, v: s.data[idx] }))
                .filter((r) => r.v != null);
            if (!rows.length) return hide();

            const cx = center(idx);
            if (isBar) {
                hl.setAttribute('x', m.l + slot * idx);
                hl.setAttribute('fill', C.hover);
            } else {
                cursor.setAttribute('x1', cx);
                cursor.setAttribute('x2', cx);
                cursor.setAttribute('visibility', 'visible');
                data.series.forEach((s, si) => {
                    const v = s.data[idx];
                    dots[si].setAttribute('cx', cx);
                    dots[si].setAttribute('cy', v == null ? 0 : y(v));
                    dots[si].setAttribute('visibility', v == null ? 'hidden' : 'visible');
                });
            }

            tip.innerHTML = `<p class="text-muted">${esc(data.titles[idx])}</p>` + rows.map(({ s, si, v }) =>
                `<p class="mt-0.5 flex items-center gap-1.5"><span class="h-2 w-2 rounded-full" style="background:${colorOf(si)}"></span>${esc(s.name)}: <b class="text-ink">${fmt(v, meta.digits)} ${meta.unit}</b></p>`
            ).join('');
            tip.classList.remove('hidden');
            const left = Math.min(Math.max(cx - tip.offsetWidth / 2, 0), W - tip.offsetWidth);
            tip.style.left = left + 'px';
            tip.style.top = m.t + 'px';
        });
        overlay.addEventListener('mouseleave', hide);
    }

    select.addEventListener('change', () => { sensor = select.value; draw(); });
    tabs.forEach((t) => t.addEventListener('click', () => { range = t.dataset.trendRange; draw(); }));
    new ResizeObserver(() => draw()).observe(box);
})();
</script>
@endpush
