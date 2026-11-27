@extends('layouts.app')

@section('title', 'Data Historis - IoT Dashboard')
@section('page-title', 'Data Historis & Laporan')
@section('page-subtitle', 'Lihat, analisis, dan export data sensor historis')

@section('content')
<div class="space-y-4">
    <!-- Toolbar: filter (corong) + export -->
    @php
        $selectedGateway = collect($gateways)->firstWhere('id', $filters['gateway_id']);
        $filterActive = $filters['gateway_id'] !== ($gateways[0]['id'] ?? null)
            || $filters['device_id'] !== null || $filters['date_from'] !== null || $filters['date_to'] !== null;
    @endphp
    <div class="flex items-center justify-end gap-2">
        @if($selectedGateway)
            <span class="mr-auto inline-flex items-center gap-2 text-sm text-muted">
                <x-icon name="server" :size="16" />
                <span class="font-medium text-ink">{{ $selectedGateway['name'] }}</span>
            </span>
        @endif
        <button type="button" onclick="openFilterModal()" title="Filter" aria-label="Filter"
                class="btn btn-outline relative bg-white/60 px-3">
            <x-icon name="filter" :size="16" />
            Filter
            @if($filterActive)
                <span class="absolute -top-1 -right-1 size-2.5 rounded-full bg-lime ring-2 ring-canvas" aria-hidden="true"></span>
            @endif
        </button>
        <a href="{{ route('historical.exportCsv', request()->query()) }}" class="btn btn-primary">
            <x-icon name="download" :size="16" />
            Ekspor CSV
        </a>
    </div>

    <!-- Modal Filter -->
    <div id="filter-modal" class="fixed inset-0 z-50 hidden items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="filter-modal-title">
        <div class="absolute inset-0 bg-ink/40 backdrop-blur-[2px]" onclick="closeFilterModal()"></div>
        <div class="card relative w-full max-w-2xl p-6 shadow-xl">
            <div class="mb-5 flex items-center justify-between gap-3">
                <h3 id="filter-modal-title" class="flex items-center gap-2 text-lg font-semibold">
                    <x-icon name="filter" :size="18" class="text-muted" />
                    Filter Data
                </h3>
                <button type="button" onclick="closeFilterModal()" aria-label="Tutup" class="grid size-9 place-items-center rounded-full text-muted transition-colors hover:bg-canvas hover:text-ink">
                    <x-icon name="x" :size="18" />
                </button>
            </div>

            <form method="GET" action="{{ route('historical.index') }}" class="space-y-5">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <!-- Gateway Filter: default gateway pertama, tidak ada opsi "semua" -->
                    <div class="min-w-0">
                        <label for="gateway_id" class="mb-1.5 block text-xs font-medium text-muted">Gateway</label>
                        <select id="gateway_id" name="gateway_id"
                                class="w-full truncate rounded-full border border-line bg-white px-4 py-2.5 text-sm text-ink outline-none focus:border-primary focus:ring-2 focus:ring-primary/15">
                            @foreach($gateways as $gateway)
                                <option value="{{ $gateway['id'] }}" @selected($filters['gateway_id'] === $gateway['id'])>{{ $gateway['name'] }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Device Filter: hanya perangkat milik gateway terpilih -->
                    <div class="min-w-0">
                        <label for="device_id" class="mb-1.5 block text-xs font-medium text-muted">Perangkat</label>
                        <select id="device_id" name="device_id"
                                class="w-full truncate rounded-full border border-line bg-white px-4 py-2.5 text-sm text-ink outline-none focus:border-primary focus:ring-2 focus:ring-primary/15">
                            <option value="">Semua Perangkat</option>
                            @foreach($devices as $device)
                                @continue($device['gateway_id'] !== $filters['gateway_id'])
                                <option value="{{ $device['id'] }}" @selected($filters['device_id'] === $device['id'])>{{ $device['name'] }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Start -->
                    <div>
                        <label for="date_from" class="mb-1.5 block text-xs font-medium text-muted">Awal</label>
                        <input id="date_from" type="datetime-local" name="date_from" value="{{ $filters['date_from'] }}"
                               class="w-full rounded-full border border-line bg-white px-4 py-2.5 text-sm text-ink outline-none focus:border-primary focus:ring-2 focus:ring-primary/15">
                    </div>

                    <!-- End -->
                    <div>
                        <label for="date_to" class="mb-1.5 block text-xs font-medium text-muted">Sampai</label>
                        <input id="date_to" type="datetime-local" name="date_to" value="{{ $filters['date_to'] }}"
                               class="w-full rounded-full border border-line bg-white px-4 py-2.5 text-sm text-ink outline-none focus:border-primary focus:ring-2 focus:ring-primary/15">
                    </div>
                </div>

                @if($errors->any())
                    <p class="text-sm text-red-600">{{ $errors->first() }}</p>
                @endif

                <div class="flex flex-wrap justify-end gap-2 border-t border-line pt-4">
                    <button type="button" onclick="closeFilterModal()" class="btn btn-outline">Batal</button>
                    <a href="{{ route('historical.index') }}" class="btn text-muted hover:bg-canvas hover:text-ink">Reset</a>
                    <button type="submit" class="btn btn-primary">Terapkan Filter</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabel Data Historis -->
    <section class="card overflow-hidden p-0">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[760px] text-left text-sm">
                <thead>
                    <tr class="border-b border-line text-xs text-muted">
                        <th class="px-4 py-3 font-medium whitespace-nowrap">Timestamp</th>
                        <th class="px-4 py-3 font-medium whitespace-nowrap">Packet #</th>
                        <th class="px-4 py-3 font-medium whitespace-nowrap">Perangkat</th>
                        <th class="px-4 py-3 text-center font-medium whitespace-nowrap">Suhu (°C)</th>
                        <th class="px-4 py-3 text-center font-medium whitespace-nowrap">Tegangan (V)</th>
                        <th class="px-4 py-3 text-center font-medium whitespace-nowrap">Arus (A)</th>
                        <th class="px-4 py-3 text-center font-medium whitespace-nowrap">Daya (W)</th>
                        <th class="px-4 py-3 text-center font-medium whitespace-nowrap">Irradiance (W/m²)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse($data as $row)
                    <tr class="transition-colors hover:bg-canvas/60">
                        <td class="px-4 py-3 whitespace-nowrap">{{ $row['timestamp'] }}</td>
                        <td class="px-4 py-3 font-mono text-muted">{{ $row['packet_number'] }}</td>
                        <td class="px-4 py-3 font-medium">{{ $row['device_name'] }}</td>
                        <td class="px-4 py-3 text-center tabular-nums">{{ $row['temperature'] }}</td>
                        <td class="px-4 py-3 text-center tabular-nums">{{ $row['voltage'] }}</td>
                        <td class="px-4 py-3 text-center tabular-nums">{{ $row['current'] }}</td>
                        <td class="px-4 py-3 text-center tabular-nums">{{ $row['power'] }}</td>
                        <td class="px-4 py-3 text-center tabular-nums">{{ $row['irradiance'] }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-4 py-10 text-center text-sm text-muted">Tidak ada data yang ditemukan</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($paginator->lastPage() > 1)
            <div class="flex justify-end border-t border-line px-4 py-3">
                <x-pagination :paginator="$paginator" />
            </div>
        @endif
    </section>

    <!-- Grafik (data dari endpoint chart-data, mengikuti filter di atas); satu garis per node -->
    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
        <section class="card">
            <h2 class="mb-4 text-base font-semibold">Grafik Suhu</h2>
            <div class="relative h-64"><canvas id="chart-temperature" aria-label="Grafik suhu per node"></canvas></div>
        </section>
        <section class="card">
            <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
                <h2 class="text-base font-semibold">Grafik Tegangan & Arus</h2>
                <div class="inline-flex gap-1 rounded-2xl bg-canvas p-1" role="group" aria-label="Pilih besaran">
                    <button type="button" data-vc-sensor="voltage" aria-pressed="true" class="rounded-xl bg-primary px-3 py-1.5 text-sm font-medium text-white shadow-sm transition-colors">Tegangan</button>
                    <button type="button" data-vc-sensor="current" aria-pressed="false" class="rounded-xl px-3 py-1.5 text-sm font-medium text-muted transition-colors hover:text-ink">Arus</button>
                </div>
            </div>
            <div class="relative h-64"><canvas id="chart-voltage-current" aria-label="Grafik tegangan atau arus per node"></canvas></div>
        </section>
        <section class="card">
            <h2 class="mb-4 text-base font-semibold">Grafik Daya</h2>
            <div class="relative h-64"><canvas id="chart-power" aria-label="Grafik daya"></canvas></div>
        </section>
        <section class="card">
            <h2 class="mb-4 text-base font-semibold">Grafik Irradiance</h2>
            <div class="relative h-64"><canvas id="chart-irradiance" aria-label="Grafik irradiance"></canvas></div>
        </section>
    </div>

    <!-- Statistik Data -->
    <section class="card">
        <h2 class="mb-4 text-base font-semibold">Statistik Data</h2>
        <div class="grid grid-cols-2 gap-3 md:grid-cols-5">
            <div class="rounded-2xl bg-canvas p-4">
                <p class="text-xs text-muted">Total Records</p>
                <p class="mt-1 text-2xl font-semibold tabular-nums">{{ number_format($stats['total'], 0, ',', '.') }}</p>
            </div>
            <div class="rounded-2xl bg-canvas p-4">
                <p class="text-xs text-muted">Periode Data</p>
                <p class="mt-1 text-sm font-semibold">{{ $stats['from'] ? $stats['from']->format('d/m/Y H:i') . ' - ' . $stats['to']->format('d/m/Y H:i') : '-' }}</p>
            </div>
            <div class="rounded-2xl bg-canvas p-4">
                <p class="text-xs text-muted">Penerimaan Paket</p>
                <p class="mt-1 text-2xl font-semibold text-lime-dark tabular-nums">{{ $stats['avg_packet_loss'] !== null ? number_format(100 - $stats['avg_packet_loss'], 1, ',', '.') : '-' }}%</p>
            </div>
            <div class="rounded-2xl bg-canvas p-4">
                <p class="text-xs text-muted">Rata-rata Packet Loss</p>
                <p class="mt-1 text-2xl font-semibold tabular-nums">{{ $stats['avg_packet_loss'] !== null ? number_format($stats['avg_packet_loss'], 2, ',', '.') : '-' }}%</p>
            </div>
            <div class="rounded-2xl bg-canvas p-4">
                <p class="text-xs text-muted">Format Ekspor</p>
                <p class="mt-1 text-sm font-semibold">CSV</p>
            </div>
        </div>
    </section>
</div>

<script>
    function openFilterModal() {
        document.getElementById('filter-modal').classList.remove('hidden');
        document.getElementById('filter-modal').classList.add('flex');
    }
    function closeFilterModal() {
        document.getElementById('filter-modal').classList.add('hidden');
        document.getElementById('filter-modal').classList.remove('flex');
    }
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeFilterModal();
    });

    // Ganti gateway → isi ulang dropdown perangkat dengan node milik gateway itu, kembali ke "Semua Perangkat"
    (function () {
        const DEVICES = @json($devices);
        const gatewaySelect = document.getElementById('gateway_id');
        const deviceSelect = document.getElementById('device_id');
        gatewaySelect?.addEventListener('change', function () {
            deviceSelect.length = 1; // sisakan "Semua Perangkat"
            DEVICES.filter((d) => String(d.gateway_id) === gatewaySelect.value)
                .forEach((d) => deviceSelect.add(new Option(d.name, d.id)));
            deviceSelect.value = '';
        });
    })();
    @if($errors->any())
        document.addEventListener('DOMContentLoaded', openFilterModal);
    @endif
</script>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.js"></script>
<script>
(function () {
    const URL_DATA = @json(route('historical.chartData')) + window.location.search;

    // Node hanya dibedakan warna (urutan sama dengan palet dashboard). Indeks = urutan node global.
    const NODE_COLORS = ['#6f9f12', '#2563eb', '#ea580c', '#8b5cf6', '#0891b2', '#db2777', '#ca8a04', '#16a34a', '#64748b', '#dc2626'];
    const colorOf = (i) => NODE_COLORS[i % NODE_COLORS.length];

    const UNITS = { temperature: '°C', voltage: 'V', current: 'A', power: 'W', irradiance: 'W/m²' };

    if (window.Chart) {
        Chart.defaults.color = '#6f766f';
        Chart.defaults.borderColor = '#e4e7e2';
        Chart.defaults.font.family = "'Plus Jakarta Sans', ui-sans-serif, system-ui, sans-serif";
    }

    const num = (v) => v == null ? '—' : v.toLocaleString('id-ID', { maximumFractionDigits: 2 });
    const esc = (t) => String(t).replace(/[&<>"]/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' })[c]);

    // Garis kursor vertikal di titik yang ditunjuk (sama seperti grafik tren dashboard)
    const cursorLine = {
        id: 'cursorLine',
        beforeDatasetsDraw(chart) {
            const active = chart.tooltip?.getActiveElements();
            if (!active?.length) return;
            const { ctx, chartArea: { top, bottom } } = chart;
            const x = active[0].element.x;
            ctx.save();
            ctx.strokeStyle = '#b9c2bd';
            ctx.lineWidth = 1;
            ctx.beginPath();
            ctx.moveTo(x, top);
            ctx.lineTo(x, bottom);
            ctx.stroke();
            ctx.restore();
        },
    };

    // Tooltip HTML gaya dashboard: satu waktu, lalu semua node dengan nilai tebal
    const htmlTooltip = (unit, title) => ({ chart, tooltip }) => {
        const box = chart.canvas.parentElement;
        let tip = box.querySelector('[data-chart-tip]');
        if (!tip) {
            tip = document.createElement('div');
            tip.dataset.chartTip = '';
            tip.className = 'pointer-events-none absolute z-10 hidden rounded-xl border border-line bg-white px-3 py-2 text-xs shadow-lg';
            box.appendChild(tip);
        }
        const rows = (tooltip.dataPoints ?? []).filter((p) => p.parsed.y != null);
        if (tooltip.opacity === 0 || !rows.length) return tip.classList.add('hidden');

        tip.innerHTML = `<p class="text-muted">${esc(title(rows[0].dataIndex))}</p>` + rows.map((p) =>
            `<p class="mt-0.5 flex items-center gap-1.5 whitespace-nowrap"><span class="h-2 w-2 rounded-full" style="background:${p.dataset.borderColor}"></span>${esc(p.dataset.label)}: <b class="text-ink">${num(p.parsed.y)} ${unit}</b></p>`
        ).join('');
        tip.classList.remove('hidden');
        const left = Math.min(Math.max(tooltip.caretX - tip.offsetWidth / 2, 0), box.clientWidth - tip.offsetWidth);
        tip.style.left = left + 'px';
        tip.style.top = chart.chartArea.top + 'px';
    };

    // Formatter waktu dalam zona dashboard (bukan zona browser).
    // Bila semua data masih di tanggal yang sama, label sumbu cukup jam saja.
    const dayKey = (ms, tz) => new Intl.DateTimeFormat('en-CA', { timeZone: tz }).format(ms);
    const timeFormatter = (tz, granularity, sameDay = false) => {
        const opts = {
            minute: sameDay
                ? { hour: '2-digit', minute: '2-digit', hour12: false }
                : { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit', hour12: false },
            day: { day: '2-digit', month: '2-digit' },
            month: { month: 'short', year: 'numeric' },
        }[granularity] ?? {};
        const f = new Intl.DateTimeFormat('id-ID', { ...opts, timeZone: tz });
        const full = new Intl.DateTimeFormat('id-ID', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit', hour12: false, timeZone: tz });
        return { tick: (ms) => f.format(ms).replace(',', ''), full: (ms) => full.format(ms).replace(',', '') };
    };

    const showMessage = (id, text, cls = 'text-muted') => {
        const canvas = document.getElementById(id);
        Chart.getChart(canvas)?.destroy();
        canvas.classList.add('hidden');
        let msg = canvas.parentElement.querySelector('[data-chart-msg]');
        if (!msg) {
            msg = document.createElement('div');
            msg.dataset.chartMsg = '';
            canvas.parentElement.appendChild(msg);
        }
        msg.className = 'flex h-64 items-center justify-center text-sm ' + cls;
        msg.textContent = text;
    };

    // Satu chart = satu sensor, satu garis per node. Sumbu X = 10 slot berjarak sama (satu per
    // putaran kirim / interval), bukan skala waktu, agar label tetap rapi walau jeda kirim tidak rata.
    const build = (id, json, sensor) => {
        const { datasets, meta } = json;
        // Node yang tidak punya nilai sensor ini (mis. paket parsial) tidak ikut digambar maupun di legenda
        const picked = datasets.filter((d) => d.sensor === sensor && d.data.some((p) => p.y !== null));
        if (!picked.length) {
            return showMessage(id, 'Belum ada data untuk filter ini.');
        }

        const canvas = document.getElementById(id);
        canvas.classList.remove('hidden');
        canvas.parentElement.querySelector('[data-chart-msg]')?.remove();
        Chart.getChart(canvas)?.destroy();

        const unit = UNITS[sensor];
        // Semua seri berbagi waktu titik yang sama (meta.ticks), urutan data = urutan slot
        const times = meta.ticks;
        const sameDay = dayKey(times[0], meta.timezone) === dayKey(times[times.length - 1], meta.timezone);
        const fmt = timeFormatter(meta.timezone, meta.granularity, sameDay);
        // Label berisi tanggal lebih panjang → dibuat miring agar 10 label muat
        const withDate = meta.granularity !== 'minute' || !sameDay;
        const isRange = meta.mode === 'range';

        new Chart(canvas, {
            type: 'line',
            data: {
                labels: times.map(fmt.tick),
                datasets: picked.map((d) => {
                    const color = colorOf(d.style);
                    return {
                        label: d.label, data: d.data.map((p) => p.y),
                        borderColor: color, backgroundColor: color,
                        pointBackgroundColor: '#fff', pointBorderColor: color,
                        pointHoverBackgroundColor: color, pointHoverBorderColor: '#fff', pointHoverBorderWidth: 2,
                        borderWidth: 2.5, pointRadius: 3, pointHoverRadius: 5, spanGaps: true,
                        // Garis melengkung halus; 'monotone' tidak melampaui nilai data di puncak/lembah
                        cubicInterpolationMode: 'monotone', borderCapStyle: 'round', borderJoinStyle: 'round',
                    };
                }),
            },
            plugins: [cursorLine],
            options: {
                responsive: true, maintainAspectRatio: false,
                // Semua node berbagi waktu titik yang sama (putaran kirim / interval) → satu tooltip untuk semua node
                interaction: { mode: 'index', intersect: false },
                scales: {
                    x: {
                        // offset: setengah slot di kiri/kanan agar titik tepi tidak menempel ke sumbu
                        offset: true,
                        grid: { display: false },
                        // Satu label per titik (10 label), sama dengan waktu di tooltip
                        ticks: { autoSkip: false, minRotation: withDate ? 45 : 0, maxRotation: 45 },
                    },
                    y: { title: { display: true, text: unit } },
                },
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { usePointStyle: true, pointStyle: 'circle', boxWidth: 8, boxHeight: 8, padding: 14 },
                    },
                    tooltip: {
                        enabled: false,
                        external: htmlTooltip(unit, (i) => (isRange ? 's.d. ' : '') + fmt.full(times[i])),
                    },
                },
            },
        });
    };

    // Toggle Tegangan/Arus: satu besaran per tampilan agar panel tidak berisi enam garis
    const vcButtons = document.querySelectorAll('[data-vc-sensor]');
    const setVcActive = (sensor) => vcButtons.forEach((b) => {
        const on = b.dataset.vcSensor === sensor;
        b.setAttribute('aria-pressed', on ? 'true' : 'false');
        b.className = 'rounded-xl px-3 py-1.5 text-sm font-medium transition-colors ' +
            (on ? 'bg-primary text-white shadow-sm' : 'text-muted hover:text-ink');
    });

    fetch(URL_DATA, { headers: { Accept: 'application/json' } })
        .then((r) => { if (!r.ok) throw new Error(r.status); return r.json(); })
        .then((json) => {
            build('chart-temperature', json, 'temperature');
            build('chart-voltage-current', json, 'voltage');
            build('chart-power', json, 'power');
            build('chart-irradiance', json, 'irradiance');

            vcButtons.forEach((b) => b.addEventListener('click', () => {
                setVcActive(b.dataset.vcSensor);
                build('chart-voltage-current', json, b.dataset.vcSensor);
            }));
        })
        .catch(() => {
            ['chart-temperature', 'chart-voltage-current', 'chart-power', 'chart-irradiance'].forEach((id) => {
                showMessage(id, 'Gagal memuat data grafik.', 'text-red-600');
            });
        });
})();
</script>
@endpush
