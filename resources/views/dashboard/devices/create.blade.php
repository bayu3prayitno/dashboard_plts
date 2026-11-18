@extends('layouts.app')

@section('title', 'Tambah Perangkat - IoT Dashboard')
@section('page-title', 'Tambah Perangkat Baru')
@section('page-subtitle', 'Registrasi node sensor baru ke sistem monitoring')

@section('content')
<div class="grid items-start gap-6 xl:grid-cols-3">
    <div class="card p-8 xl:col-span-2">
        <form method="POST" action="{{ route('devices.store') }}" class="space-y-6">
            @csrf

            <!-- Node ID -->
            <div>
                <label class="block text-sm font-semibold text-ink mb-2">ID Node</label>
                <input type="number" name="node_id" min="1" value="{{ old('node_id', $nextNodeId) }}" placeholder="1" class="w-full px-4 py-2 rounded-full border border-line bg-white outline-none focus:ring-2 focus:ring-primary/15 focus:border-primary" required>
                <p class="text-xs text-muted mt-1">Terisi otomatis dengan nomor berikutnya. Nilai ini nanti dimasukkan ke firmware Node setelah perangkat disimpan.</p>
                @error('node_id')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>

            <!-- Device Name -->
            <div>
                <label class="block text-sm font-semibold text-ink mb-2">Nama Perangkat</label>
                <input type="text" name="name" value="{{ old('name') }}" placeholder="Node Sensor 01" class="w-full px-4 py-2 rounded-full border border-line bg-white outline-none focus:ring-2 focus:ring-primary/15 focus:border-primary" required>
                @error('name')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>

            <!-- Location -->
            <div>
                <label class="block text-sm font-semibold text-ink mb-2">Lokasi</label>
                <input type="text" name="location" value="{{ old('location') }}" placeholder="Lokasi A" class="w-full px-4 py-2 rounded-full border border-line bg-white outline-none focus:ring-2 focus:ring-primary/15 focus:border-primary" required>
                @error('location')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>

            <!-- Gateway -->
            <div>
                <label class="block text-sm font-semibold text-ink mb-2">Gateway Terhubung</label>
                <select name="gateway_id" class="w-full px-4 py-2 rounded-full border border-line bg-white outline-none focus:ring-2 focus:ring-primary/15 focus:border-primary" required>
                    <option value="">Pilih Gateway</option>
                    @foreach($gateways as $gateway)
                        <option value="{{ $gateway->id }}" @selected(old('gateway_id') == $gateway->id)>{{ $gateway->name }}</option>
                    @endforeach
                </select>
                @if($gateways->isEmpty())
                    <p class="text-xs text-red-600 mt-1">Belum ada Gateway. Tambahkan Gateway terlebih dahulu.</p>
                @endif
                @error('gateway_id')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>

            <!-- Buttons -->
            <div class="flex gap-4 pt-6 border-t">
                <button type="submit" class="flex-1 btn btn-primary font-semibold">Simpan Perangkat</button>
                <a href="{{ route('devices.index') }}" class="flex-1 btn btn-outline font-semibold text-center">Batal</a>
            </div>
        </form>
    </div>

    <!-- Panduan -->
    <aside class="card space-y-5">
        <div>
            <h3 class="text-base font-semibold text-ink">Langkah Setelah Disimpan</h3>
            <p class="text-sm text-muted">Agar data perangkat masuk ke dashboard.</p>
        </div>

        <ol class="space-y-4 text-sm text-ink">
            <li class="flex gap-3">
                <span class="grid size-6 shrink-0 place-items-center rounded-full bg-primary-soft text-xs font-semibold text-lime-dark">1</span>
                <span>Simpan perangkat, lalu buka detailnya untuk melihat NODE_ID, GATEWAY_CODE, dan MQTT Topic.</span>
            </li>
            <li class="flex gap-3">
                <span class="grid size-6 shrink-0 place-items-center rounded-full bg-primary-soft text-xs font-semibold text-lime-dark">2</span>
                <span>Masukkan nilai tersebut ke firmware Node.</span>
            </li>
            <li class="flex gap-3">
                <span class="grid size-6 shrink-0 place-items-center rounded-full bg-primary-soft text-xs font-semibold text-lime-dark">3</span>
                <span>Pastikan Node mengirim <span class="font-mono text-xs">packet_no</span> yang bertambah di setiap paket.</span>
            </li>
        </ol>

        @if($gateways->isEmpty())
            <div class="rounded-2xl bg-red-50 p-4 text-sm text-red-700">
                Belum ada Gateway. Tambahkan Gateway terlebih dahulu sebelum mendaftarkan perangkat.
            </div>
        @endif
    </aside>
</div>
@endsection
