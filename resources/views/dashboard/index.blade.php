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
        ['route' => 'alerts.index', 'icon' => 'bell-ring', 'label' => 'Peringatan aktif', 'value' => count($activeAlerts)],
    ];
@endphp

@section('content')
    {{-- ================= HERO ================= --}}
    {{-- Margin negatif membuat header (cari + notifikasi) menumpang di atas banner --}}
    <section class="hero-surface relative -mt-16 mb-4 overflow-hidden rounded-3xl px-5 pt-20 pb-5 sm:px-7 md:min-h-[25rem]">
        <img src="{{ asset('images/plts-3D.png') }}" alt=""
            class="pointer-events-none mx-auto -mb-2 h-52 object-contain drop-shadow-xl select-none md:absolute md:top-1/2 md:left-[36%] md:mb-0 md:h-[112%] md:-translate-y-[46%] xl:left-[40%]">

        <div class="relative flex h-full flex-col gap-6 md:min-h-[19rem] md:justify-between">
            <div class="max-w-sm">
                <h1 class="text-3xl leading-tight font-medium tracking-tight uppercase sm:text-4xl">Monitoring<br>PLTS Cerdas</h1>
                <p class="mt-2 text-sm text-muted">Pantau node sensor dan gateway LoRa secara real-time.</p>
                <div class="mt-4 flex flex-wrap gap-2">
                    <a href="{{ route('devices.create') }}" class="btn btn-primary"><x-icon name="plus" :size="16" /> Tambah Perangkat</a>
                    <a href="{{ route('historical.exportCsv') }}" class="btn btn-outline bg-white/60"><x-icon name="download" :size="16" /> Ekspor CSV</a>
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
                        <p class="mt-1 text-xs text-muted" data-trend-sub>Rata-rata harian dari semua perangkat, dalam W.</p>
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
                                            <span class="text-xs whitespace-nowrap text-muted" title="{{ $d['last_seen']->translatedFormat('d M Y H:i') }} WIB">{{ Format::relTime($d['last_seen']) }}</span>
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
                <ul class="mt-3 space-y-1.5 text-xs">
                    @foreach ($links as $d)
                        <li class="flex justify-between gap-2">
                            <span class="truncate text-muted">{{ $d['nama'] }}</span>
                            <span class="font-medium tabular-nums">{{ Format::num(100 - $d['packet_loss'], 1) }}%</span>
                        </li>
                    @endforeach
                </ul>
            </section>

            {{-- Status gateway --}}
            <section class="rounded-3xl bg-primary p-5 text-white">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-base font-semibold">Status Gateway</h2>
                    <a href="{{ route('gateway.index') }}" aria-label="Buka halaman gateway" class="grid size-8 place-items-center rounded-full bg-white/10 hover:bg-white/20">
                        <x-icon name="arrow-up-right" :size="16" />
                    </a>
                </div>
                <p class="text-sm text-white/60">{{ $gateway['nama'] }}</p>
                <p class="mt-1 flex items-center gap-2 text-3xl font-semibold">
                    <span class="size-2.5 rounded-full {{ $gateway['status'] === 'online' ? 'bg-lime' : 'bg-red-400' }}"></span>
                    {{ $gateway['status'] === 'online' ? 'Online' : 'Offline' }}
                </p>
                <ul class="mt-4 space-y-2 text-sm">
                    @foreach ([
                        ['server', 'MQTT', $gateway['mqtt_status'] === 'connected' ? 'Terhubung' : 'Terputus'],
                        ['globe', 'Internet', $gateway['internet_status'] === 'connected' ? 'Terhubung' : 'Terputus'],
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
                @if (count($activeAlerts) === 0)
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
                    <h2 class="text-base font-semibold">Perangkat</h2>
                    <a href="{{ route('devices.index') }}" class="rounded-full border border-line px-3 py-1 text-xs font-medium hover:bg-canvas">Kelola</a>
                </div>
                <ul class="space-y-1">
                    @foreach ($devices as $d)
                        <li>
                            <a href="{{ route('devices.show', $d['id']) }}" class="flex items-center gap-3 rounded-2xl p-2 hover:bg-canvas">
                                <span class="grid size-9 shrink-0 place-items-center rounded-xl bg-primary-soft text-sm font-semibold">{{ $d['node_id'] }}</span>
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate text-sm font-medium">{{ $d['nama'] }}</span>
                                    <span class="block truncate text-xs text-muted">{{ $d['lokasi'] }} · {{ Format::relTime($d['last_seen']) }}</span>
                                </span>
                                <x-status-badge :status="$d['status']" />
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
(function () {
    const TREND = @json($trend);
    const SENSORS = @json($sensors);
    const card = document.getElementById('trend-card');
    const box = card.querySelector('[data-trend-chart]');
    const tip = card.querySelector('[data-trend-tip]');
    const select = card.querySelector('[data-trend-sensor]');
    const tabs = card.querySelectorAll('[data-trend-range]');
    const NS = 'http://www.w3.org/2000/svg';
    const C = { axis: '#6f766f', grid: '#eceeea', dark: '#262a26', lime: '#a8d93a', limeDark: '#6f9f12' };
    let sensor = 'power', range = '7d';

    const fmt = (v, d) => v == null ? '—' : v.toLocaleString('id-ID', { minimumFractionDigits: d, maximumFractionDigits: d });
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

    function draw() {
        const meta = SENSORS[sensor];
        const data = TREND[sensor][range];
        card.querySelector('[data-trend-label]').textContent = meta.label;
        card.querySelector('[data-trend-sub]').textContent =
            `${range === '7d' ? 'Rata-rata harian' : 'Rata-rata per 30 menit'} dari semua perangkat, dalam ${meta.unit}.`;
        tabs.forEach((t) => {
            const on = t.dataset.trendRange === range;
            t.className = 'rounded-xl px-3 py-1.5 text-sm font-medium transition-colors ' +
                (on ? 'bg-primary text-white shadow-sm' : 'text-muted hover:text-ink');
        });

        box.querySelector('svg')?.remove();
        box.querySelector('[data-empty]')?.remove();
        const values = data.map((d) => d.value).filter((v) => v != null);
        if (!values.length) {
            box.insertAdjacentHTML('beforeend', '<p data-empty class="py-8 text-center text-sm text-muted">Belum ada data pada rentang ini.</p>');
            return;
        }

        const W = box.clientWidth, H = box.clientHeight;
        const m = { t: 8, r: 8, b: 26, l: 48 };
        const iw = W - m.l - m.r, ih = H - m.t - m.b;
        const isBar = range === '7d';
        const ticks = niceTicks(isBar ? 0 : Math.min(...values), Math.max(...values));
        const y0 = ticks[0], y1 = ticks.at(-1);
        const y = (v) => m.t + ih - ((v - y0) / (y1 - y0)) * ih;
        const svg = el('svg', { width: W, height: H, class: 'block' });
        box.prepend(svg);

        ticks.forEach((t) => {
            el('line', { x1: m.l, x2: W - m.r, y1: y(t), y2: y(t), stroke: C.grid }, svg);
            const tx = el('text', { x: m.l - 8, y: y(t) + 4, 'text-anchor': 'end', 'font-size': 11, fill: C.axis }, svg);
            tx.textContent = fmt(t, t % 1 ? 1 : 0);
        });

        const showTip = (d, x, yy) => {
            tip.innerHTML = `<p class="text-muted">${d.title}</p><p class="mt-0.5 text-sm font-semibold text-ink">${fmt(d.value, meta.digits)} ${meta.unit}</p>`;
            tip.classList.remove('hidden');
            const left = Math.min(Math.max(x - tip.offsetWidth / 2, 0), W - tip.offsetWidth);
            tip.style.left = left + 'px';
            tip.style.top = Math.max(yy - tip.offsetHeight - 10, 0) + 'px';
        };
        const hideTip = () => tip.classList.add('hidden');

        if (isBar) {
            const max = Math.max(...values);
            const slot = iw / data.length;
            const bw = Math.min(40, slot * 0.6);
            data.forEach((d, i) => {
                const cx = m.l + slot * (i + 0.5);
                const lbl = el('text', { x: cx, y: H - 6, 'text-anchor': 'middle', 'font-size': 11, fill: C.axis }, svg);
                lbl.textContent = d.label;
                const hover = el('rect', { x: m.l + slot * i, y: m.t, width: slot, height: ih, fill: 'transparent' }, svg);
                if (d.value == null) return;
                const top = y(d.value), h = y(y0) - top, r = Math.min(8, bw / 2, h);
                const x = cx - bw / 2, b = y(y0);
                el('path', {
                    d: `M${x} ${b}V${top + r}Q${x} ${top} ${x + r} ${top}H${x + bw - r}Q${x + bw} ${top} ${x + bw} ${top + r}V${b}Z`,
                    fill: d.value === max ? C.lime : C.dark, 'pointer-events': 'none',
                }, svg);
                hover.addEventListener('mouseenter', () => { hover.setAttribute('fill', '#f4f5f4'); svg.insertBefore(hover, svg.querySelector('path')); showTip(d, cx, top); });
                hover.addEventListener('mouseleave', () => { hover.setAttribute('fill', 'transparent'); hideTip(); });
            });
        } else {
            const x = (i) => m.l + (i / (data.length - 1)) * iw;
            const gid = 'trend-grad';
            const defs = el('defs', {}, svg);
            const lg = el('linearGradient', { id: gid, x1: 0, y1: 0, x2: 0, y2: 1 }, defs);
            el('stop', { offset: '0%', 'stop-color': C.lime, 'stop-opacity': 0.45 }, lg);
            el('stop', { offset: '100%', 'stop-color': C.lime, 'stop-opacity': 0 }, lg);

            // Pecah menjadi segmen agar rentang tanpa data tetap terputus
            let seg = [];
            const flush = () => {
                if (seg.length > 1) {
                    const line = seg.map(([i, v], k) => `${k ? 'L' : 'M'}${x(i)} ${y(v)}`).join('');
                    el('path', { d: `${line}L${x(seg.at(-1)[0])} ${y(y0)}L${x(seg[0][0])} ${y(y0)}Z`, fill: `url(#${gid})` }, svg);
                    el('path', { d: line, fill: 'none', stroke: C.limeDark, 'stroke-width': 2, 'stroke-linejoin': 'round' }, svg);
                }
                seg = [];
            };
            data.forEach((d, i) => (d.value == null ? flush() : seg.push([i, d.value])));
            flush();

            data.forEach((d, i) => {
                if (i % 6 === 0) {
                    const t = el('text', { x: x(i), y: H - 6, 'text-anchor': 'middle', 'font-size': 11, fill: C.axis }, svg);
                    t.textContent = d.label;
                }
            });

            const cursor = el('line', { y1: m.t, y2: m.t + ih, stroke: '#b9c2bd', visibility: 'hidden' }, svg);
            const dot = el('circle', { r: 4, fill: C.limeDark, stroke: '#fff', 'stroke-width': 2, visibility: 'hidden' }, svg);
            const overlay = el('rect', { x: m.l, y: m.t, width: iw, height: ih, fill: 'transparent' }, svg);
            overlay.addEventListener('mousemove', (e) => {
                const px = e.clientX - svg.getBoundingClientRect().left;
                const i = Math.round(((px - m.l) / iw) * (data.length - 1));
                const d = data[Math.max(0, Math.min(data.length - 1, i))];
                if (!d || d.value == null) { hideTip(); cursor.setAttribute('visibility', 'hidden'); dot.setAttribute('visibility', 'hidden'); return; }
                const cx = x(data.indexOf(d));
                cursor.setAttribute('x1', cx); cursor.setAttribute('x2', cx); cursor.setAttribute('visibility', 'visible');
                dot.setAttribute('cx', cx); dot.setAttribute('cy', y(d.value)); dot.setAttribute('visibility', 'visible');
                showTip(d, cx, y(d.value));
            });
            overlay.addEventListener('mouseleave', () => { hideTip(); cursor.setAttribute('visibility', 'hidden'); dot.setAttribute('visibility', 'hidden'); });
        }
    }

    select.addEventListener('change', () => { sensor = select.value; draw(); });
    tabs.forEach((t) => t.addEventListener('click', () => { range = t.dataset.trendRange; draw(); }));
    new ResizeObserver(() => draw()).observe(box);
})();
</script>
@endpush
