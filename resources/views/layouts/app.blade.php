<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'OmahIoT Dashboard')</title>
    {{-- Sidebar terbuka secara default; pilihan terakhir diingat per browser. Diterapkan sebelum render agar tidak berkedip. --}}
    <script>
        try { if (localStorage.getItem('sidebar') === 'closed') document.documentElement.classList.add('sb-collapsed'); } catch (e) {}
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
@php
    $pageNames = [
        'devices' => 'Perangkat', 'historical' => 'Histori', 'alerts' => 'Peringatan', 'gateway' => 'Gateway',
        'settings.users' => 'Pengguna', 'settings.auditLog' => 'Log Aktivitas', 'settings' => 'Pengaturan',
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
        ['route' => 'settings.users', 'is' => 'settings.users', 'icon' => 'users', 'label' => 'Pengguna'],
        ['route' => 'settings.system', 'is' => ['settings.system', 'settings.index', 'settings.scope'], 'icon' => 'settings', 'label' => 'Pengaturan'],
        ['route' => 'settings.auditLog', 'is' => 'settings.auditLog', 'icon' => 'scroll-text', 'label' => 'Log Aktivitas'],
    ];
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
                    <x-icon name="sun" :size="22" />
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
                <a href="{{ route('historical.exportCsv') }}" aria-label="Unduh CSV 24 jam" class="group nav-item">
                    <x-icon name="download" :size="19" />
                    <span class="nav-label">Unduh CSV 24 jam</span>
                    <span class="nav-hint">Unduh CSV 24 jam</span>
                </a>
                @auth
                    <form method="POST" action="{{ route('logout') }}" class="contents">
                        @csrf
                        <button type="submit" aria-label="Logout" class="group nav-item">
                            <x-icon name="log-out" :size="19" />
                            <span class="nav-label">Logout</span>
                            <span class="nav-hint">Logout</span>
                        </button>
                    </form>
                @else
                    <a href="{{ route('login') }}" aria-label="Masuk" class="group nav-item">
                        <x-icon name="log-out" :size="19" class="rotate-180" />
                        <span class="nav-label">Masuk</span>
                        <span class="nav-hint">Masuk</span>
                    </a>
                @endauth
                <a href="{{ route('settings.index') }}" aria-label="Profil {{ $layoutUser['nama'] }}"
                   class="group relative mt-2 flex items-center gap-3 rounded-2xl px-1 lg:sb-collapsed:px-0">
                    <span class="grid size-11 shrink-0 place-items-center rounded-2xl bg-lime text-sm font-bold text-ink">{{ $initials }}</span>
                    <span class="nav-label">
                        <span class="block text-sm leading-tight font-semibold">{{ $layoutUser['nama'] }}</span>
                        <span class="block text-xs text-white/60">{{ $layoutUser['role'] }}</span>
                    </span>
                    <span class="nav-hint">{{ $layoutUser['nama'] }} · {{ $layoutUser['role'] }}</span>
                </a>
            </div>
        </div>
    </aside>

    {{-- ================= KONTEN ================= --}}
    <div class="min-w-0 flex-1">
        {{-- Header transparan; di Dashboard ia menumpang di atas banner hero --}}
        <header class="relative z-20 flex h-16 items-center gap-3 px-1 sm:px-4">
            <button type="button" data-sidebar-toggle aria-label="Buka menu"
                class="grid size-10 shrink-0 place-items-center rounded-full bg-white shadow-sm lg:hidden">
                <x-icon name="menu" :size="18" />
            </button>
            <p class="hidden items-center gap-2 text-sm text-muted sm:flex">
                <x-icon name="layout-grid" :size="15" /> {{ $pageName }}
            </p>

            <div class="ml-auto flex min-w-0 flex-1 items-center justify-end gap-3">
                {{-- Cari perangkat --}}
                <div class="relative min-w-0 flex-1 sm:w-72 sm:flex-none" data-device-search>
                    <x-icon name="search" :size="16" class="pointer-events-none absolute top-1/2 left-4 -translate-y-1/2 text-muted" />
                    <input type="search" placeholder="Cari perangkat…" aria-label="Cari perangkat (nama, ID, lokasi)" autocomplete="off"
                        class="w-full rounded-full bg-white py-2.5 pr-4 pl-10 text-sm shadow-sm outline-none focus:ring-2 focus:ring-lime">
                    <div data-results class="absolute top-full right-0 left-0 z-30 mt-2 hidden rounded-2xl border border-line bg-white p-2 shadow-lg"></div>
                </div>

                {{-- Notifikasi --}}
                <div class="relative" data-notifications>
                    <button type="button" data-toggle aria-label="Notifikasi, {{ count($layoutFreshAlerts) }} peringatan baru"
                        class="relative grid size-10 place-items-center rounded-full bg-white shadow-sm hover:bg-primary-soft">
                        <x-icon name="bell" :size="18" />
                        @if (count($layoutFreshAlerts) > 0)
                            <span class="absolute -top-0.5 -right-0.5 grid size-4.5 place-items-center rounded-full bg-red-600 text-[10px] font-semibold text-white">{{ count($layoutFreshAlerts) }}</span>
                        @endif
                    </button>
                    <div data-panel class="absolute right-0 z-30 mt-2 hidden w-80 max-w-[calc(100vw-2rem)] rounded-2xl border border-line bg-white p-2 shadow-lg">
                        <p class="px-3 py-2 text-sm font-semibold">Peringatan baru</p>
                        @forelse (array_slice($layoutFreshAlerts, 0, 5) as $a)
                            <a href="{{ route('alerts.index') }}" class="block rounded-xl px-3 py-2 hover:bg-canvas">
                                <span class="block text-sm font-medium">{{ $a['device'] }}</span>
                                <span class="block text-xs text-muted">{{ $a['text'] }} · {{ \App\Support\Format::relTime($a['waktu']) }}</span>
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
</script>
@stack('scripts')
</body>
</html>
