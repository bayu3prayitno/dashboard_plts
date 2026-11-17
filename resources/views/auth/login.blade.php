<!DOCTYPE html>
<html lang="id">
<head>
    @section('title', 'Masuk - OmahIoT')
    @include('layouts.partials.head')
</head>
@php
    $input = 'w-full rounded-xl border border-line bg-white px-3 py-2.5 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/15';
    // Akun demo hanya ditampilkan di lingkungan lokal (dibuat oleh DatabaseSeeder)
    $demo = app()->isLocal() ? [
        ['Super Admin', 'superadmin@omahiot.example'],
    ] : [];
@endphp
<body class="font-sans antialiased">
<x-page-progress />
<div class="grid min-h-screen p-3 lg:grid-cols-2 lg:p-4">

    {{-- Panel hero (desktop) --}}
    <div data-hero class="hero-surface relative hidden flex-col justify-between overflow-hidden rounded-3xl p-10 lg:flex">
        {{-- Langit: matahari/bulan mengikuti jam (sama dengan dashboard) --}}
        <x-hero-sky :peak-clear="24" />

        <div class="relative flex items-center gap-2.5">
            <span class="grid size-10 place-items-center rounded-2xl bg-primary text-lime"><x-logo :size="25" /></span>
            <span data-hero-title class="text-lg font-semibold transition-colors duration-1000">OmahIoT</span>
        </div>
        <img data-hero-img src="{{ asset('images/plts-3D.webp') }}" alt="" width="1000" height="1000" fetchpriority="high" decoding="async"
            class="pointer-events-none relative mx-auto max-h-[52vh] w-auto object-contain drop-shadow-xl select-none">
        <div class="relative">
            <p data-hero-title class="max-w-md text-4xl leading-tight font-medium tracking-tight uppercase transition-colors duration-1000">Monitoring PLTS Cerdas</p>
            <p data-hero-subtitle class="mt-3 max-w-md text-sm text-muted transition-colors duration-1000">
                Suhu, tegangan, arus, daya, dan irradiance tiap 5 menit, lengkap dengan histori, peringatan, dan kualitas link LoRa.
            </p>
        </div>
    </div>

    {{-- Form --}}
    <div class="flex flex-col items-center justify-center gap-6 p-4">
        {{-- Logo untuk layar kecil --}}
        <div class="flex items-center gap-2.5 lg:hidden">
            <span class="grid size-10 place-items-center rounded-2xl bg-primary text-lime"><x-icon name="sun" :size="20" /></span>
            <span class="text-lg font-semibold">OmahIoT</span>
        </div>

        <form method="POST" action="{{ route('login.store') }}" class="w-full max-w-sm space-y-4 rounded-3xl bg-white p-7" novalidate>
            @csrf
            <div>
                <h1 class="text-2xl font-semibold">Masuk</h1>
                <p class="mt-1 text-sm text-muted">Gunakan email atau username Anda.</p>
            </div>

            <label class="block text-sm">
                <span class="mb-1.5 block font-medium">Email / username</span>
                <input name="login" value="{{ old('login') }}" autocomplete="username" autofocus required
                    class="{{ $input }} @error('login') border-red-300 @enderror">
            </label>

            <label class="block text-sm">
                <span class="mb-1.5 block font-medium">Kata sandi</span>
                <span class="relative block">
                    <input type="password" name="password" autocomplete="current-password" required
                        class="{{ $input }} pr-11 @error('password') border-red-300 @enderror" data-password>
                    <button type="button" data-toggle-password aria-label="Tampilkan kata sandi"
                        class="absolute top-1/2 right-2 grid size-8 -translate-y-1/2 place-items-center rounded-lg text-muted hover:bg-canvas hover:text-ink">
                        <x-icon name="eye" :size="16" />
                    </button>
                </span>
            </label>

            <label class="flex items-center gap-2 text-sm text-muted">
                <input type="checkbox" name="remember" value="1" @checked(old('remember'))
                    class="size-4 rounded border-line accent-primary">
                Ingat saya
            </label>

            @if ($errors->any())
                <p class="text-sm text-red-700" role="alert">{{ $errors->first() }}</p>
            @endif

            <button type="submit" class="btn btn-primary w-full">Masuk</button>

            @if ($demo)
                <div class="rounded-2xl bg-canvas p-3 text-xs text-muted">
                    <p class="font-medium text-ink">Akun demo (kata sandi: password)</p>
                    <div class="mt-2 flex flex-wrap gap-2">
                        @foreach ($demo as [$role, $email])
                            <button type="button" class="btn btn-outline btn-sm" data-demo="{{ $email }}">Isi sebagai {{ $role }}</button>
                        @endforeach
                    </div>
                </div>
            @endif
        </form>
    </div>
</div>

<script>
(function () {
    const form = document.querySelector('form');
    const pwd = form.querySelector('[data-password]');
    const toggle = form.querySelector('[data-toggle-password]');
    toggle.addEventListener('click', () => {
        const show = pwd.type === 'password';
        pwd.type = show ? 'text' : 'password';
        toggle.setAttribute('aria-label', show ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi');
    });
    form.querySelectorAll('[data-demo]').forEach((b) => b.addEventListener('click', () => {
        form.login.value = b.dataset.demo;
        pwd.value = 'password';
    }));
})();
</script>
</body>
</html>
