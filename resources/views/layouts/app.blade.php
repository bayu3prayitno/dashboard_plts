<!DOCTYPE html>
<html lang="id">
<head>
    @include('layouts.partials.head')
</head>
@php
    $pageNames = [
        'devices' => 'Perangkat', 'historical' => 'Histori', 'alerts' => 'Peringatan', 'gateway' => 'Gateway',
        'settings.users' => 'Pengguna', 'settings.auditLog' => 'Log Aktivitas', 'settings' => 'Pengaturan',
        'profile' => 'Profil',
    ];
    $pageName = 'Dashboard';
    foreach ($pageNames as $prefix => $name) {
        if (Route::is($prefix, $prefix.'.*')) { $pageName = $name; break; }
    }

    $navMain = [
        ['route' => 'dashboard.index', 'is' => 'dashboard.*', 'icon' => 'layout-grid', 'label' => 'Dashboard'],
        ['route' => 'devices.index', 'is' => 'devices.*', 'icon' => 'cpu', 'label' => 'Perangkat'],
        ['route' => 'historical.index', 'is' => 'historical.*', 'icon' => 'history', 'label' => 'Histori'],
        ['route' => 'alerts.index', 'is' => 'alerts.*', 'icon' => 'bell-ring', 'label' => 'Peringatan', 'badge' => $layoutActiveAlerts],
        ['route' => 'gateway.index', 'is' => 'gateway.*', 'icon' => 'router', 'label' => 'Gateway'],
    ];
    $navAdmin = [
        ['route' => 'settings.users', 'is' => 'settings.users', 'icon' => 'users', 'label' => 'Pengguna', 'super' => true],
        ['route' => 'settings.system', 'is' => ['settings.system', 'settings.index', 'settings.scope'], 'icon' => 'settings', 'label' => 'Pengaturan', 'super' => true],
        ['route' => 'settings.auditLog', 'is' => 'settings.auditLog', 'icon' => 'scroll-text', 'label' => 'Log Aktivitas'],
    ];
    // Menu khusus Super Admin disembunyikan untuk Admin (route-nya belum dikunci di server)
    $navAdmin = array_filter($navAdmin, fn ($item) => ! ($item['super'] ?? false) || $layoutUser['super']);
    $initials = collect(explode(' ', $layoutUser['nama']))->map(fn ($w) => mb_substr($w, 0, 1))->take(2)->join('');
@endphp
<body class="font-sans antialiased">
<div class="mx-auto flex min-h-screen max-w-[1600px] gap-4 p-3 lg:p-4">

    {{-- Overlay laci ponsel --}}
    <div data-sidebar-close class="fixed inset-0 z-40 hidden bg-black/50 max-lg:sb-open:block"></div>

    {{-- ================= SIDEBAR ================= --}}
    <aside id="sidebar"
        class="z-50 shrink-0 bg-primary transition-[width,translate] duration-200
               max-lg:fixed max-lg:inset-y-0 max-lg:left-0 max-lg:w-64 max-lg:-translate-x-full max-lg:sb-open:translate-x-0
               lg:sticky lg:top-4 lg:h-[calc(100vh-2rem)] lg:w-60 lg:rounded-3xl lg:sb-collapsed:w-[4.5rem]">
        <div class="flex h-full flex-col gap-1.5 overflow-x-hidden overflow-y-auto px-3 py-4 text-white
                    lg:sb-collapsed:items-center lg:sb-collapsed:overflow-visible lg:sb-collapsed:px-2">

            {{-- Logo = tombol buka/tutup sidebar (desktop); di ponsel menutup laci --}}
            <button type="button" data-sidebar-toggle aria-label="OmahIoT, buka/tutup sidebar"
                class="group relative mb-5 flex w-full items-center gap-3 rounded-2xl px-1 text-left lg:sb-collapsed:w-auto lg:sb-collapsed:px-0">
                <span class="grid size-11 shrink-0 place-items-center rounded-2xl bg-primary-dark text-lime transition-colors group-hover:bg-black">
                    <x-logo :size="30" />
                </span>
                <span class="nav-label text-lg font-semibold">OmahIoT</span>
                <span class="nav-hint">Buka sidebar</span>
            </button>

            @foreach ($navMain as $item)
                <a href="{{ route($item['route']) }}" aria-label="{{ $item['label'] }}"
                   class="group nav-item {{ Route::is($item['is']) ? 'nav-item-active' : '' }}">
                    <x-icon :name="$item['icon']" :size="19" />
                    <span class="nav-label">{{ $item['label'] }}</span>
                    <span class="nav-hint">{{ $item['label'] }}</span>
                    @if (($item['badge'] ?? 0) > 0)
                        <span class="nav-badge">{{ $item['badge'] }}</span>
                    @endif
                </a>
            @endforeach

            <span class="mx-2 my-2 h-px shrink-0 bg-white/15 lg:sb-collapsed:mx-0 lg:sb-collapsed:w-7"></span>

            @foreach ($navAdmin as $item)
                <a href="{{ route($item['route']) }}" aria-label="{{ $item['label'] }}"
                   class="group nav-item {{ Route::is($item['is']) ? 'nav-item-active' : '' }}">
                    <x-icon :name="$item['icon']" :size="19" />
                    <span class="nav-label">{{ $item['label'] }}</span>
                    <span class="nav-hint">{{ $item['label'] }}</span>
                </a>
            @endforeach

            <div class="mt-auto flex w-full flex-col gap-1.5 lg:sb-collapsed:items-center">
                <span class="mx-2 my-2 h-px shrink-0 bg-white/15 lg:sb-collapsed:mx-0 lg:sb-collapsed:w-7"></span>

                <a href="{{ route('historical.exportCsv') }}" aria-label="Unduh CSV 24 jam" class="group nav-item">
                    <x-icon name="download" :size="19" />
                    <span class="nav-label">Unduh CSV 24 jam</span>
                    <span class="nav-hint">Unduh CSV 24 jam</span>
                </a>

                <a href="{{ route('profile.show') }}" aria-label="Profil {{ $layoutUser['nama'] }}"
                   class="group nav-item mt-1 {{ Route::is('profile.*') ? 'nav-item-active' : '' }}">
                    <span class="grid size-9 shrink-0 place-items-center rounded-xl bg-lime text-xs font-bold text-ink">{{ $initials }}</span>
                    <span class="nav-label leading-tight">
                        <span class="block truncate text-sm font-semibold">{{ $layoutUser['nama'] }}</span>
                        <span class="block truncate text-xs text-white/60">{{ $layoutUser['role'] }}</span>
                    </span>
                    <span class="nav-hint">{{ $layoutUser['nama'] }} · {{ $layoutUser['role'] }}</span>
                </a>
            </div>
        </div>
    </aside>

    {{-- ================= KONTEN ================= --}}
    <div class="min-w-0 flex-1">
        {{-- Header transparan; di Dashboard ia menumpang di atas banner hero --}}
        <header data-app-header class="sticky top-0 z-30 flex h-16 items-center gap-3 px-1 transition-colors sm:px-4 [&.is-stuck]:bg-canvas/20 [&.is-stuck]:backdrop-blur-md">
            <button type="button" data-sidebar-toggle aria-label="Buka menu"
                class="grid size-10 shrink-0 place-items-center rounded-full bg-white text-lime-dark shadow-sm lg:hidden">
                <x-logo :size="22" />
            </button>
            <p class="hidden items-center gap-2 text-sm text-muted sm:flex">
                <x-icon name="layout-grid" :size="15" /> {{ $pageName }}
            </p>

            <div class="ml-auto flex min-w-0 flex-1 items-center justify-end gap-3">
                {{-- Jam Digital --}}
                <div class="hidden items-center gap-2 rounded-full bg-white/50 px-4 py-2.5 text-sm font-medium tabular-nums text-ink shadow-sm backdrop-blur-sm md:flex">
                    <x-icon name="clock" :size="16" class="text-muted" />
                    <span id="live-clock">00:00:00</span>
                </div>

                {{-- Cari perangkat --}}
                <div class="relative min-w-0 flex-1 sm:w-72 sm:flex-none" data-device-search>
                    <x-icon name="search" :size="16" class="pointer-events-none absolute top-1/2 left-4 -translate-y-1/2 text-muted" />
                    <input type="search" placeholder="Cari perangkat…" aria-label="Cari perangkat (nama, ID, lokasi)" autocomplete="off"
                        class="w-full rounded-full bg-white py-2.5 pr-4 pl-10 text-sm shadow-sm outline-none focus:ring-2 focus:ring-lime">
                    <div data-results class="absolute top-full right-0 left-0 z-30 mt-2 hidden rounded-2xl border border-line bg-white p-2 shadow-lg"></div>
                </div>

                {{-- Notifikasi --}}
                <div class="relative" data-notifications>
                    <button type="button" data-toggle aria-label="Notifikasi, {{ $layoutFreshCount }} peringatan baru"
                        class="relative grid size-10 place-items-center rounded-full bg-white shadow-sm hover:bg-primary-soft">
                        <x-icon name="bell" :size="18" />
                        @if ($layoutFreshCount > 0)
                            <span class="absolute -top-0.5 -right-0.5 grid size-4.5 place-items-center rounded-full bg-red-600 text-[10px] font-semibold text-white">{{ $layoutFreshCount }}</span>
                        @endif
                    </button>
                    <div data-panel class="absolute right-0 z-30 mt-2 hidden w-80 max-w-[calc(100vw-2rem)] rounded-2xl border border-line border-gray-200 bg-white p-2 shadow-lg">
                        <p class="px-3 py-2 text-sm font-semibold">Peringatan baru</p>
                        @forelse ($layoutFreshAlerts as $a)
                            <a href="{{ route('alerts.index') }}" class="block px-3 py-2 hover:bg-canvas border-t border-gray-200 first:border-t-0 ">
                                <p class="block text-sm font-medium">{{ $a['category'] }}.  <span class="text-[10px] text-muted font-italic">{{ \App\Support\Format::relTime($a['waktu']) }}</span></p>
                                <p class="block text-xs text-muted"><span class="font-bold">{{ $a['device'] }}: </span> {{ $a['text'] }}.</p>
                            </a>
                        @empty
                            <p class="px-3 pb-3 text-sm text-muted">Tidak ada peringatan baru.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </header>

        <main>
            @if (trim($__env->yieldContent('page-title')) !== '')
                <div class="mb-5 flex flex-wrap items-end justify-between gap-3 px-1">
                    <div>
                        <h1 class="text-2xl font-semibold sm:text-3xl">@yield('page-title')</h1>
                        @if (trim($__env->yieldContent('page-subtitle')) !== '')
                            <p class="mt-1 text-sm text-muted">@yield('page-subtitle')</p>
                        @endif
                    </div>
                </div>
            @endif

            @yield('content')
        </main>
    </div>
</div>

<script>
(function () {
    const root = document.documentElement;
    const desktop = window.matchMedia('(min-width: 1024px)');

    // Sidebar: desktop = lebar ↔ rel ikon (diingat); ponsel = laci
    document.querySelectorAll('[data-sidebar-toggle]').forEach((btn) => {
        btn.addEventListener('click', () => {
            if (desktop.matches) {
                const closed = root.classList.toggle('sb-collapsed');
                try { localStorage.setItem('sidebar', closed ? 'closed' : 'open'); } catch (e) {}
            } else {
                root.classList.toggle('sb-open');
            }
        });
    });
    document.querySelectorAll('[data-sidebar-close]').forEach((el) =>
        el.addEventListener('click', () => root.classList.remove('sb-open')));
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            root.classList.remove('sb-open');
            document.querySelector('[data-notifications] [data-panel]')?.classList.add('hidden');
        }
    });

    // Header sticky: beri latar hanya saat halaman di-scroll agar banner hero tetap utuh di atas
    const header = document.querySelector('[data-app-header]');
    const onScroll = () => header.classList.toggle('is-stuck', window.scrollY > 4);
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();

    // Cari perangkat
    const devices = @json($layoutDevices);
    const search = document.querySelector('[data-device-search]');
    const input = search.querySelector('input');
    const results = search.querySelector('[data-results]');
    const esc = (s) => String(s).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const render = () => {
        const q = input.value.trim().toLowerCase();
        if (!q) return results.classList.add('hidden');
        const hits = devices.filter((d) => `${d.nama} ${d.lokasi} node ${d.node_id}`.toLowerCase().includes(q));
        results.innerHTML = hits.length
            ? hits.map((d) => `<a href="${esc(d.url)}" class="block w-full rounded-xl px-3 py-2 text-left text-sm hover:bg-canvas">
                    <span class="font-medium">${esc(d.nama)}</span>
                    <span class="block text-xs text-muted">Node ID ${esc(d.node_id)} · ${esc(d.lokasi)}</span></a>`).join('')
            : '<p class="px-3 py-2 text-sm text-muted">Perangkat tidak ditemukan.</p>';
        results.classList.remove('hidden');
    };
    input.addEventListener('input', render);
    input.addEventListener('focus', render);
    input.addEventListener('blur', () => setTimeout(() => results.classList.add('hidden'), 150));

    // Notifikasi
    const notif = document.querySelector('[data-notifications]');
    const panel = notif.querySelector('[data-panel]');
    notif.querySelector('[data-toggle]').addEventListener('click', (e) => {
        e.stopPropagation();
        panel.classList.toggle('hidden');
    });
    document.addEventListener('click', (e) => { if (!notif.contains(e.target)) panel.classList.add('hidden'); });
})();

// Live Clock (mengikuti timezone dari setting)
(function () {
    const clock = document.getElementById('live-clock');
    if (!clock) return;

    const timezone = '{{ $layoutTimezone ?? "Asia/Jakarta" }}';

    function updateClock() {
        const now = new Date();
        const formatter = new Intl.DateTimeFormat('en-US', {
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
            hour12: false,
            timeZone: timezone
        });
        clock.textContent = formatter.format(now);
    }

    updateClock();
    setInterval(updateClock, 1000);
})();

// Toast peringatan baru: polling ringan tiap 30 detik, hanya saat tab terlihat.
// id terakhir disimpan per tab agar pindah halaman tidak memicu request tambahan/toast ganda.
(function () {
    const KEY = 'alertLastId';
    const INTERVAL = 30000;
    let lastId = null;
    try { lastId = sessionStorage.getItem(KEY); } catch (e) {}

    const poll = async () => {
        if (document.hidden) return;
        try {
            const res = await fetch('{{ route('api.live.alerts') }}' + (lastId ? '?after=' + lastId : ''),
                { headers: { Accept: 'application/json' } });
            if (!res.ok) return;
            const data = await res.json();
            data.alerts.forEach((a) => showToast(`🚨 ${a.device}: ${a.message}`, 'error'));
            lastId = data.last_id;
            try { sessionStorage.setItem(KEY, lastId); } catch (e) {}
        } catch (e) {}
    };

    // Kunjungan pertama tab: ambil titik awal. Setelahnya cukup tunggu interval.
    if (!lastId) setTimeout(poll, 3000);
    setInterval(poll, INTERVAL);
})();
</script>
{{-- Notifikasi toast (global): pesan flash dari server atau showToast() dari JavaScript --}}
<div class="pointer-events-none fixed inset-x-0 top-5 z-[60] flex justify-center px-4 sm:inset-x-auto sm:right-6 sm:justify-end" aria-live="polite">
    <div data-toast-box class="pointer-events-auto invisible flex w-full max-w-sm translate-y-2 items-start gap-3 rounded-2xl border bg-white p-4 opacity-0 shadow-xl shadow-black/10 transition-all duration-300">
        <span data-toast-icon class="grid size-9 shrink-0 place-items-center rounded-full">
            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 13l4 4L19 7"/></svg>
        </span>
        <div class="min-w-0 flex-1">
            <p data-toast-title class="text-sm font-semibold text-ink"></p>
            <p data-toast-message class="mt-0.5 break-words text-sm text-muted"></p>
        </div>
        <button type="button" data-toast-close aria-label="Tutup" class="-m-1 rounded-full p-1 text-muted hover:bg-canvas hover:text-ink">
            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg>
        </button>
    </div>
</div>
<script>
(function () {
    const box = document.querySelector('[data-toast-box]');
    const icon = box.querySelector('[data-toast-icon]');
    const title = box.querySelector('[data-toast-title]');
    const message = box.querySelector('[data-toast-message]');
    const tones = {
        success: { border: 'border-emerald-200', icon: ['bg-emerald-100', 'text-emerald-700'], title: 'Berhasil' },
        error: { border: 'border-red-200', icon: ['bg-red-100', 'text-red-700'], title: 'Gagal' },
    };
    const borders = ['border-emerald-200', 'border-red-200'];
    const iconTones = ['bg-emerald-100', 'text-emerald-700', 'bg-red-100', 'text-red-700'];
    let timer;

    const hide = () => {
        box.classList.remove('opacity-100', 'translate-y-0');
        box.classList.add('opacity-0', 'translate-y-2', 'invisible');
    };

    window.showToast = (text, type = 'success') => {
        const tone = tones[type] ?? tones.success;
        box.classList.remove(...borders);
        box.classList.add(tone.border);
        icon.classList.remove(...iconTones);
        icon.classList.add(...tone.icon);
        title.textContent = tone.title;
        message.textContent = text;
        box.classList.remove('opacity-0', 'translate-y-2', 'invisible');
        box.classList.add('opacity-100', 'translate-y-0');
        clearTimeout(timer);
        timer = setTimeout(hide, 4500);
    };

    box.querySelector('[data-toast-close]').addEventListener('click', hide);
})();
</script>
@if(session('success'))
<script>document.addEventListener('DOMContentLoaded', () => showToast(@json(session('success')), 'success'));</script>
@endif
@if(session('error'))
<script>document.addEventListener('DOMContentLoaded', () => showToast(@json(session('error')), 'error'));</script>
@endif
@stack('scripts')
</body>
</html>
