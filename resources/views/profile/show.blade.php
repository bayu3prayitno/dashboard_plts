@extends('layouts.app')

@section('title', 'Profil Saya - OmahIoT Dashboard')
@section('page-title', 'Profil Saya')
@section('page-subtitle', 'Informasi akun yang sedang Anda gunakan')

@php
    $initials = collect(explode(' ', $user->name))->map(fn ($w) => mb_substr($w, 0, 1))->take(2)->join('');
    // Urutan mengikuti alur grid dua kolom: kiri-kanan per baris
    $fields = [
        ['icon' => 'user', 'label' => 'Nama lengkap', 'value' => $user->name],
        ['icon' => 'shield-check', 'label' => 'Peran', 'value' => $user->roleLabel()],
        ['icon' => 'at-sign', 'label' => 'Username', 'value' => $user->username],
        ['icon' => 'clock', 'label' => 'Terdaftar sejak', 'value' => $user->created_at?->locale('id')->translatedFormat('d F Y') ?? '—'],
        ['icon' => 'mail', 'label' => 'Email', 'value' => $user->email],
    ];

    $input = 'w-full rounded-xl border border-line bg-white px-3 py-2.5 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/15';
    // Modal: animasi masuk/keluar CSS murni (Tailwind v4: starting: + transition-discrete)
    $dialog = 'm-auto w-[calc(100%-2rem)] max-w-md rounded-3xl bg-white p-6 text-ink shadow-2xl
               opacity-0 scale-95 transition-all transition-discrete duration-200 ease-out motion-reduce:transition-none
               open:opacity-100 open:scale-100 starting:open:opacity-0 starting:open:scale-95
               backdrop:bg-black/50 backdrop:opacity-0 backdrop:transition-all backdrop:transition-discrete backdrop:duration-200
               open:backdrop:opacity-100 starting:open:backdrop:opacity-0';
    // Modal mana yang dibuka otomatis saat validasi gagal
    $openModal = $errors->profile->any() ? 'edit-profile' : ($errors->password->any() ? 'edit-password' : null);
@endphp

@section('content')
@if (session('status'))
    <div data-flash class="mb-4 flex items-center gap-3 rounded-2xl bg-primary-soft px-4 py-3 text-sm text-ink" role="status">
        <span class="grid size-7 shrink-0 place-items-center rounded-full bg-lime text-ink"><x-icon name="check" :size="15" /></span>
        <span class="flex-1">{{ session('status') }}</span>
        <button type="button" aria-label="Tutup" class="grid size-7 place-items-center rounded-full text-muted hover:bg-white hover:text-ink" onclick="this.parentElement.remove()">
            <x-icon name="x" :size="15" />
        </button>
    </div>
@endif

<div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
    {{-- ================= KARTU PROFIL (1/3) ================= --}}
    <section class="relative flex flex-col overflow-hidden rounded-3xl bg-primary p-6 text-white">
        <div class="relative flex flex-1 flex-col items-center text-center">
            <div class="relative">
                <span class="grid size-28 place-items-center rounded-full bg-lime text-4xl font-bold text-ink ring-4 ring-white/15">{{ $initials }}</span>
            </div>
            <h2 class="mt-4 w-full truncate text-2xl font-semibold">{{ $user->name }}</h2>
            <span class="mt-3 inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium {{ $user->is_active ? 'bg-lime text-ink' : 'bg-red-500/20 text-red-200' }}">
                <span class="size-1.5 rounded-full {{ $user->is_active ? 'bg-ink' : 'bg-red-300' }}"></span>
                {{ $user->is_active ? 'Akun aktif' : 'Akun nonaktif' }}
            </span>

            <div class="mt-auto flex w-full flex-col gap-2 pt-8">
                <button type="button" data-open-modal="edit-profile" class="btn w-full bg-lime text-ink hover:bg-primary-light/90">
                    <x-icon name="pencil" :size="16" /> Edit Profil
                </button>
                <button type="button" data-open-modal="edit-password" class="btn w-full bg-white text-ink hover:bg-primary-soft">
                    <x-icon name="key-round" :size="16" /> Ubah Password
                </button>
            </div>
        </div>
    </section>

    {{-- ================= DETAIL AKUN (2/3) ================= --}}
    <div class="card flex flex-col sm:p-6 lg:col-span-2">
        <h3 class="mb-2 text-sm font-semibold">Detail akun</h3>
        <dl class="grid grid-cols-1 gap-x-8 sm:grid-cols-2">
            @foreach ($fields as $f)
                <div class="flex items-center gap-3 border-b border-line py-3.5 {{ $loop->last ? 'sm:border-b-0' : '' }}">
                    <span class="grid size-9 shrink-0 place-items-center rounded-xl bg-primary-soft text-lime-dark">
                        <x-icon :name="$f['icon']" :size="17" />
                    </span>
                    <div class="min-w-0">
                        <dt class="text-xs text-muted">{{ $f['label'] }}</dt>
                        <dd class="truncate text-sm font-medium">{{ $f['value'] }}</dd>
                    </div>
                </div>
            @endforeach
        </dl>

        <div class="mt-auto flex justify-end pt-8">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="btn border border-red-200 bg-red-50 text-red-700 hover:bg-red-100">
                    <x-icon name="log-out" :size="16" /> Logout
                </button>
            </form>
        </div>
    </div>
</div>

{{-- ================= MODAL: EDIT PROFIL ================= --}}
<dialog id="edit-profile" class="{{ $dialog }}" aria-labelledby="edit-profile-title">
    <form method="POST" action="{{ route('profile.update') }}" class="space-y-4">
        @csrf
        @method('PUT')
        <div class="flex items-start justify-between gap-3">
            <div>
                <h2 id="edit-profile-title" class="text-lg font-semibold">Edit Profil</h2>
                <p class="mt-0.5 text-sm text-muted">Perbarui nama, username, dan email Anda.</p>
            </div>
            <button type="button" data-close-modal aria-label="Tutup" class="grid size-9 shrink-0 place-items-center rounded-full text-muted hover:bg-canvas hover:text-ink">
                <x-icon name="x" :size="18" />
            </button>
        </div>

        <label class="block text-sm">
            <span class="mb-1.5 block font-medium">Nama lengkap</span>
            <input name="name" value="{{ old('name', $user->name) }}" autocomplete="name" required
                class="{{ $input }} @error('name', 'profile') border-red-300 @enderror">
            @error('name', 'profile') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
        </label>

        <label class="block text-sm">
            <span class="mb-1.5 block font-medium">Username</span>
            <input name="username" value="{{ old('username', $user->username) }}" autocomplete="username" required
                class="{{ $input }} @error('username', 'profile') border-red-300 @enderror">
            @error('username', 'profile') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
        </label>

        <label class="block text-sm">
            <span class="mb-1.5 block font-medium">Email</span>
            <input type="email" name="email" value="{{ old('email', $user->email) }}" autocomplete="email" required
                class="{{ $input }} @error('email', 'profile') border-red-300 @enderror">
            @error('email', 'profile') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
        </label>

        <div class="flex justify-end gap-2 pt-2">
            <button type="button" data-close-modal class="btn btn-outline">Batal</button>
            <button type="submit" class="btn btn-primary">Simpan</button>
        </div>
    </form>
</dialog>

{{-- ================= MODAL: UBAH PASSWORD ================= --}}
<dialog id="edit-password" class="{{ $dialog }}" aria-labelledby="edit-password-title">
    <form method="POST" action="{{ route('profile.password') }}" class="space-y-4">
        @csrf
        @method('PUT')
        <div class="flex items-start justify-between gap-3">
            <div>
                <h2 id="edit-password-title" class="text-lg font-semibold">Ubah Password</h2>
                <p class="mt-0.5 text-sm text-muted">Masukkan kata sandi saat ini, lalu kata sandi baru minimal 8 karakter.</p>
            </div>
            <button type="button" data-close-modal aria-label="Tutup" class="grid size-9 shrink-0 place-items-center rounded-full text-muted hover:bg-canvas hover:text-ink">
                <x-icon name="x" :size="18" />
            </button>
        </div>

        @foreach ([
            ['name' => 'current_password', 'label' => 'Kata sandi saat ini', 'autocomplete' => 'current-password'],
            ['name' => 'password', 'label' => 'Kata sandi baru', 'autocomplete' => 'new-password'],
            ['name' => 'password_confirmation', 'label' => 'Ulangi kata sandi baru', 'autocomplete' => 'new-password'],
        ] as $pw)
            <label class="block text-sm">
                <span class="mb-1.5 block font-medium">{{ $pw['label'] }}</span>
                <span class="relative block">
                    <input type="password" name="{{ $pw['name'] }}" autocomplete="{{ $pw['autocomplete'] }}" required
                        class="{{ $input }} pr-11 @error($pw['name'], 'password') border-red-300 @enderror">
                    <button type="button" data-toggle-password aria-label="Tampilkan kata sandi"
                        class="absolute top-1/2 right-2 grid size-8 -translate-y-1/2 place-items-center rounded-lg text-muted hover:bg-canvas hover:text-ink">
                        <x-icon name="eye" :size="16" />
                    </button>
                </span>
                @error($pw['name'], 'password') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
            </label>
        @endforeach

        <div class="flex justify-end gap-2 pt-2">
            <button type="button" data-close-modal class="btn btn-outline">Batal</button>
            <button type="submit" class="btn btn-primary">Simpan kata sandi</button>
        </div>
    </form>
</dialog>
@endsection

@push('scripts')
<script>
(function () {
    // Buka modal dari tombol pemicu
    document.querySelectorAll('[data-open-modal]').forEach((btn) =>
        btn.addEventListener('click', () => document.getElementById(btn.dataset.openModal)?.showModal()));

    document.querySelectorAll('dialog').forEach((dialog) => {
        dialog.querySelectorAll('[data-close-modal]').forEach((btn) => btn.addEventListener('click', () => dialog.close()));
        // Klik di backdrop (di luar panel) menutup modal
        dialog.addEventListener('click', (e) => {
            const r = dialog.getBoundingClientRect();
            const outside = e.clientX < r.left || e.clientX > r.right || e.clientY < r.top || e.clientY > r.bottom;
            if (outside) dialog.close();
        });
    });

    // Tampilkan / sembunyikan kata sandi
    document.querySelectorAll('[data-toggle-password]').forEach((btn) =>
        btn.addEventListener('click', () => {
            const input = btn.parentElement.querySelector('input');
            input.type = input.type === 'password' ? 'text' : 'password';
        }));

    // Validasi gagal: buka kembali modal yang bersangkutan
    @if ($openModal)
        document.getElementById(@json($openModal))?.showModal();
    @endif
})();
</script>
@endpush
