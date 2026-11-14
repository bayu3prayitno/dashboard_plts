@extends('layouts.app')

@section('title', 'Ubah Gateway - OmahIoT')

@section('content')
<div class="mx-auto max-w-lg">
    <h1 class="mb-5 text-2xl font-semibold sm:text-3xl">Ubah Gateway</h1>

    <form method="POST" action="{{ route('gateway.update', $gateway) }}" class="card space-y-4">
        @csrf
        @method('PUT')

        <label class="block text-sm">
            <span class="mb-1.5 block font-medium">Kode gateway</span>
            <input value="{{ $gateway->gateway_code }}" disabled
                class="w-full rounded-xl border border-line bg-canvas px-3 py-2.5 text-sm text-muted">
        </label>

        <label class="block text-sm">
            <span class="mb-1.5 block font-medium">Nama gateway</span>
            <input name="name" value="{{ old('name', $gateway->name) }}" required
                class="w-full rounded-xl border border-line bg-white px-3 py-2.5 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/15">
            @error('name')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror
        </label>

        <label class="block text-sm">
            <span class="mb-1.5 block font-medium">Lokasi</span>
            <input name="location" value="{{ old('location', $gateway->location) }}" required
                class="w-full rounded-xl border border-line bg-white px-3 py-2.5 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/15">
            @error('location')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror
        </label>

        <div class="flex justify-end gap-2 pt-2">
            <a href="{{ route('gateway.show', $gateway) }}" class="btn text-muted hover:bg-canvas hover:text-ink">Batal</a>
            <button type="submit" class="btn btn-primary">Simpan</button>
        </div>
    </form>
</div>
@endsection
