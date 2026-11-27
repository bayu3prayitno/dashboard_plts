@extends('layouts.app')

@section('title', 'Pengaturan Sistem - IoT Dashboard')
@section('page-title', 'Pengaturan Sistem Global')
@section('page-subtitle', 'Konfigurasi zona waktu, retensi data, dan offline timeout')

@section('content')
@php
    $svg = fn (string $d, string $cls = 'h-5 w-5') => '<svg class="'.$cls.'" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="'.$d.'"/></svg>';

    $icons = [
        'globe' => 'M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0121 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0112 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 013 12c0-1.605.42-3.113 1.157-4.418',
        'calendar' => 'M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5',
        'clock' => 'M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z',
        'signal' => 'M9.348 14.652a3.75 3.75 0 010-5.304m5.304 0a3.75 3.75 0 010 5.304m-7.425 2.121a6.75 6.75 0 010-9.546m9.546 0a6.75 6.75 0 010 9.546M5.106 18.894c-3.808-3.807-3.808-9.98 0-13.788m13.788 0c3.808 3.807 3.808 9.98 0 13.788M12 12h.008v.008H12V12zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z',
        'info' => 'M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z',
        'save' => 'M9 3.75H6.912a2.25 2.25 0 00-2.15 1.588L2.35 13.177a2.25 2.25 0 00-.1.661V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18v-4.162c0-.224-.034-.447-.1-.661L19.24 5.338a2.25 2.25 0 00-2.15-1.588H15M2.25 13.5h3.86a2.25 2.25 0 012.012 1.244l.256.512a2.25 2.25 0 002.013 1.244h3.218a2.25 2.25 0 002.013-1.244l.256-.512a2.25 2.25 0 012.013-1.244h3.859M12 3v8.25m0 0l-3-3m3 3l3-3',
        'chevron' => 'M19.5 8.25l-7.5 7.5-7.5-7.5',
        'network' => 'M7.217 10.907a2.25 2.25 0 100 2.186m0-2.186c.18.324.283.696.283 1.093s-.103.77-.283 1.093m0-2.186l9.566-5.314m-9.566 7.5l9.566 5.314m0 0a2.25 2.25 0 103.935 2.186 2.25 2.25 0 00-3.935-2.186zm0-12.814a2.25 2.25 0 103.933-2.185 2.25 2.25 0 00-3.933 2.185z',
        'server' => 'M21.75 17.25v-.228a4.5 4.5 0 00-.12-1.03l-2.268-9.64a3.375 3.375 0 00-3.285-2.602H7.923a3.375 3.375 0 00-3.285 2.602l-2.268 9.64a4.5 4.5 0 00-.12 1.03v.228m19.5 0a3 3 0 01-3 3H5.25a3 3 0 01-3-3m19.5 0a3 3 0 00-3-3H5.25a3 3 0 00-3 3',
        'plug' => 'M8.25 6.75h7.5M9 3.75v3m6-3v3M6.75 6.75h10.5v4.5a5.25 5.25 0 01-10.5 0v-4.5z',
        'user' => 'M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z',
        'lock' => 'M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z',
        'eye' => 'M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178zM15 12a3 3 0 11-6 0 3 3 0 016 0z',
        'shield' => 'M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285',
        'bolt' => 'M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z',
    ];

    $labelCls = 'flex items-center gap-2 text-sm font-semibold text-ink mb-2';
    $chromeCls = 'flex items-stretch overflow-hidden rounded-xl border border-line bg-white focus-within:border-lime-dark focus-within:ring-2 focus-within:ring-lime/40';
    $tileCls = 'grid w-11 shrink-0 place-items-center border-r border-line bg-canvas/60 text-muted';
    $fieldCls = 'min-w-0 flex-1 bg-transparent px-3 py-2.5 text-sm text-ink outline-none disabled:text-muted';
    $hintCls = 'mt-1.5 flex items-center gap-1.5 text-xs text-muted';
@endphp

<div class="grid grid-cols-1 gap-6 xl:grid-cols-2">

    {{-- Kartu 1: Waktu & retensi --}}
    <section class="card flex flex-col p-6 md:p-8">
        <header class="mb-6 flex items-start gap-4 border-b border-line pb-6">
            <div class="grid size-12 shrink-0 place-items-center rounded-2xl bg-primary-soft text-lime-dark hidden">
                {!! $svg($icons['clock'], 'size-6') !!}
            </div>
            <div>
                <h2 class="text-lg font-bold text-ink">Pengaturan Waktu &amp; Retensi Data</h2>
                <p class="mt-0.5 text-sm text-muted">Atur zona waktu, periode retensi, dan timeout offline perangkat.</p>
            </div>
        </header>

        <form method="POST" action="{{ route('settings.systemUpdate') }}" class="@container flex flex-1 flex-col">
            @csrf

            <div class="mb-6 grid grid-cols-1 gap-5 @md:grid-cols-2">
                <div>
                    <label class="{{ $labelCls }}">Timezone</label>
                    <div class="{{ $chromeCls }} relative">
                        <span class="{{ $tileCls }}">{!! $svg($icons['globe'], 'size-5') !!}</span>
                        <select name="timezone" class="{{ $fieldCls }} appearance-none pr-10">
                            <option value="Asia/Jakarta" @selected($settings['timezone'] === 'Asia/Jakarta')>Asia/Jakarta (WIB)</option>
                            <option value="Asia/Makassar" @selected($settings['timezone'] === 'Asia/Makassar')>Asia/Makassar (WITA)</option>
                            <option value="Asia/Jayapura" @selected($settings['timezone'] === 'Asia/Jayapura')>Asia/Jayapura (WIT)</option>
                            <option value="UTC" @selected($settings['timezone'] === 'UTC')>UTC</option>
                        </select>
                        <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-muted">{!! $svg($icons['chevron'], 'size-4') !!}</span>
                    </div>
                </div>

                <div>
                    <label class="{{ $labelCls }}">Retensi Data (Hari)</label>
                    <div class="{{ $chromeCls }}">
                        <span class="{{ $tileCls }}">{!! $svg($icons['calendar'], 'size-5') !!}</span>
                        <input type="number" name="data_retention_days" value="{{ $settings['data_retention_days'] }}" min="30" class="{{ $fieldCls }}">
                    </div>
                    <p class="{{ $hintCls }}">{!! $svg($icons['info'], 'size-4 text-lime-dark') !!} Data lebih lama dari ini akan diarsipkan atau dihapus</p>
                </div>

                <div>
                    <label class="{{ $labelCls }}">Offline Timeout (Menit)</label>
                    <div class="{{ $chromeCls }}">
                        <span class="{{ $tileCls }}">{!! $svg($icons['clock'], 'size-5') !!}</span>
                        <input type="number" name="offline_timeout_minutes" value="{{ $settings['offline_timeout_minutes'] }}" min="5" class="{{ $fieldCls }}">
                    </div>
                    <p class="{{ $hintCls }}">{!! $svg($icons['info'], 'size-4 text-lime-dark') !!} Durasi sebelum perangkat dianggap offline</p>
                </div>

                <div>
                    <label class="{{ $labelCls }}">Beacon Interval (Menit)</label>
                    <div class="{{ $chromeCls }}">
                        <span class="{{ $tileCls }}">{!! $svg($icons['signal'], 'size-5') !!}</span>
                        <input type="number" name="beacon_interval_minutes" value="{{ $settings['beacon_interval_minutes'] }}" disabled class="{{ $fieldCls }} cursor-not-allowed bg-canvas/40">
                    </div>
                    <p class="{{ $hintCls }}">{!! $svg($icons['info'], 'size-4 text-lime-dark') !!} Interval pemancar Gateway (read-only, diatur di firmware)</p>
                </div>
            </div>

            <button type="submit" class="btn btn-primary mt-auto w-full rounded-xl py-3">
                {!! $svg($icons['save'], 'size-5') !!} Simpan Pengaturan
            </button>
        </form>
    </section>

    {{-- Kartu 2: Broker MQTT --}}
    <section class="card flex flex-col p-6 md:p-8">
        <header class="mb-6 flex items-start gap-4 border-b border-line pb-6">
            <div class="grid size-12 shrink-0 place-items-center rounded-2xl bg-violet-50 text-violet-600 hidden">
                {!! $svg($icons['network'], 'size-6') !!}
            </div>
            <div>
                <h2 class="text-lg font-bold text-ink">Broker MQTT</h2>
                <p class="mt-0.5 text-sm text-muted">Koneksi subscriber ke broker untuk menerima data dan status Gateway.</p>
            </div>
        </header>

        <form method="POST" action="{{ route('settings.mqttUpdate') }}" class="flex flex-1 flex-col">
            @csrf

            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label class="{{ $labelCls }}">Host</label>
                    <div class="{{ $chromeCls }}">
                        <span class="{{ $tileCls }}">{!! $svg($icons['server'], 'size-5') !!}</span>
                        <input type="text" name="mqtt_host" value="{{ old('mqtt_host', $mqtt['mqtt_host']) }}" required class="{{ $fieldCls }}">
                    </div>
                </div>
                <div>
                    <label class="{{ $labelCls }}">Port</label>
                    <div class="{{ $chromeCls }}">
                        <span class="{{ $tileCls }}">{!! $svg($icons['plug'], 'size-5') !!}</span>
                        <input type="number" name="mqtt_port" value="{{ old('mqtt_port', $mqtt['mqtt_port']) }}" min="1" max="65535" required class="{{ $fieldCls }}">
                    </div>
                </div>

                <div>
                    <label class="{{ $labelCls }}">Username</label>
                    <div class="{{ $chromeCls }}">
                        <span class="{{ $tileCls }}">{!! $svg($icons['user'], 'size-5') !!}</span>
                        <input type="text" name="mqtt_username" value="{{ old('mqtt_username', $mqtt['mqtt_username']) }}" placeholder="Masukkan username" autocomplete="off" class="{{ $fieldCls }}">
                    </div>
                </div>
                <div class="sm:col-span-2">
                    <label class="{{ $labelCls }}">Password</label>
                    <div class="{{ $chromeCls }}">
                        <span class="{{ $tileCls }}">{!! $svg($icons['lock'], 'size-5') !!}</span>
                        <input type="password" name="mqtt_password" value="" autocomplete="new-password" placeholder="{{ $mqttHasPassword ? '•••••••• (tersimpan, kosongkan jika tidak diubah)' : 'Kosongkan jika tanpa password' }}" class="{{ $fieldCls }}">
                        <button type="button" onclick="const i=this.previousElementSibling; i.type=i.type==='password'?'text':'password';" class="grid w-11 shrink-0 place-items-center text-muted hover:text-ink" aria-label="Tampilkan atau sembunyikan password">{!! $svg($icons['eye'], 'size-5') !!}</button>
                    </div>
                    @if ($mqttHasPassword)
                        <label class="mt-2 flex items-center gap-2 text-xs text-muted">
                            <input type="checkbox" name="mqtt_clear_password" value="1" class="rounded border-line text-lime-dark focus:ring-lime"> Hapus password tersimpan
                        </label>
                    @endif
                </div>
            </div>

            <label class="mb-6 mt-5 inline-flex items-center gap-2 text-sm font-semibold text-ink">
                <input type="checkbox" name="mqtt_tls" value="1" @checked($mqtt['mqtt_tls'] === '1') class="rounded border-line text-lime-dark focus:ring-lime">
                Gunakan TLS
                <span class="text-lime-dark">{!! $svg($icons['shield'], 'size-4') !!}</span>
            </label>

            <button type="submit" class="mt-auto inline-flex w-full items-center justify-center gap-2 rounded-xl bg-slate-700 py-3 text-sm font-medium text-white transition-colors hover:bg-slate-800">
                {!! $svg($icons['bolt'], 'size-5') !!} Simpan Konfigurasi MQTT
            </button>
        </form>
    </section>

    {{-- Kartu 3: Tombol darurat developer (hanya Super Admin) --}}
    @if ($canResetData)
        @php
            $resetErrors = $errors->getBag('reset');
            $checkedScopes = old('scopes', ['measurements', 'alerts']);
        @endphp
        @php
            $warnIcon = 'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z';
        @endphp

        {{-- Hanya ikon kecil; kartu Zona Bahaya muncul sebagai modal saat ikon dipencet --}}
        <div class="flex justify-end xl:col-span-2">
            <button type="button" onclick="openResetModal()" title="Zona Bahaya: Reset Data" aria-label="Buka Zona Bahaya: Reset Data"
                    class="grid size-10 place-items-center rounded-full border border-red-200 bg-white/60 text-red-500 transition-colors hover:bg-red-50 hover:text-red-700">
                {!! $svg($warnIcon, 'size-5') !!}
            </button>
        </div>

        <div id="reset-modal" class="fixed inset-0 z-50 {{ $resetErrors->any() ? 'flex' : 'hidden' }} items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="reset-modal-title">
        <div class="absolute inset-0 bg-ink/40 backdrop-blur-[2px]" onclick="closeResetModal()"></div>
        <section class="card relative flex max-h-[90vh] w-full max-w-3xl flex-col overflow-y-auto border-red-200 p-6 shadow-xl md:p-8">
            <header class="mb-6 flex items-start gap-4 border-b border-red-100 pb-6">
                <div class="grid size-12 shrink-0 place-items-center rounded-2xl bg-red-50 text-red-600">
                    {!! $svg($warnIcon, 'size-6') !!}
                </div>
                <div class="flex-1">
                    <h2 id="reset-modal-title" class="text-lg font-bold text-red-700">Zona Bahaya: Reset Data</h2>
                    <p class="mt-0.5 text-sm text-muted">Tombol darurat untuk mengosongkan data. Pengguna, gateway, perangkat, ambang batas, dan pengaturan <b class="text-ink">tidak</b> ikut terhapus. Tindakan ini tidak bisa dibatalkan.</p>
                </div>
                <button type="button" onclick="closeResetModal()" aria-label="Tutup" class="grid size-9 shrink-0 place-items-center rounded-full text-muted transition-colors hover:bg-canvas hover:text-ink">
                    {!! $svg('M6 18L18 6M6 6l12 12', 'size-5') !!}
                </button>
            </header>

            <form method="POST" action="{{ route('settings.resetData') }}" class="grid grid-cols-1 gap-6 lg:grid-cols-2"
                  onsubmit="return confirm('Yakin hapus data yang dipilih? Data tidak bisa dikembalikan.');">
                @csrf

                <fieldset>
                    <legend class="{{ $labelCls }}">Data yang dihapus</legend>
                    <div class="space-y-2">
                        @foreach ($resetScopes as $key => $label)
                            <label class="flex items-start gap-2 text-sm text-ink">
                                <input type="checkbox" name="scopes[]" value="{{ $key }}" @checked(in_array($key, $checkedScopes, true))
                                       class="mt-0.5 rounded border-line text-red-600 focus:ring-red-300">
                                {{ $label }}
                            </label>
                        @endforeach
                    </div>
                    @if ($resetErrors->has('scopes'))
                        <p class="mt-2 text-xs text-red-600">{{ $resetErrors->first('scopes') }}</p>
                    @endif
                </fieldset>

                <div class="flex flex-col gap-4">
                    <div>
                        <label class="{{ $labelCls }}" for="confirm_text">Ketik <code class="rounded bg-red-50 px-1.5 text-red-700">RESET</code> untuk konfirmasi</label>
                        <div class="{{ $chromeCls }}">
                            <input id="confirm_text" type="text" name="confirm_text" autocomplete="off" required class="{{ $fieldCls }}">
                        </div>
                        @if ($resetErrors->has('confirm_text'))
                            <p class="mt-1.5 text-xs text-red-600">{{ $resetErrors->first('confirm_text') }}</p>
                        @endif
                    </div>
                    <div>
                        <label class="{{ $labelCls }}" for="reset_password">Password akun Anda</label>
                        <div class="{{ $chromeCls }}">
                            <span class="{{ $tileCls }}">{!! $svg($icons['lock'], 'size-5') !!}</span>
                            <input id="reset_password" type="password" name="password" autocomplete="current-password" required class="{{ $fieldCls }}">
                        </div>
                        @if ($resetErrors->has('password'))
                            <p class="mt-1.5 text-xs text-red-600">{{ $resetErrors->first('password') }}</p>
                        @endif
                    </div>

                    <button type="submit" class="mt-auto inline-flex w-full items-center justify-center gap-2 rounded-xl bg-red-600 py-3 text-sm font-medium text-white transition-colors hover:bg-red-700">
                        {!! $svg('M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0', 'size-5') !!}
                        Reset Data Sekarang
                    </button>
                </div>
            </form>
        </section>
        </div>

        <script>
            function openResetModal() {
                const m = document.getElementById('reset-modal');
                m.classList.remove('hidden');
                m.classList.add('flex');
                document.getElementById('confirm_text')?.focus();
            }
            function closeResetModal() {
                const m = document.getElementById('reset-modal');
                m.classList.add('hidden');
                m.classList.remove('flex');
            }
            document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeResetModal(); });
        </script>
    @endif
</div>
@endsection
