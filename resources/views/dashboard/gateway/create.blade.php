@extends('layouts.app')

@section('title', 'Tambah Gateway - OmahIoT')

@section('content')
<div class="mx-auto max-w-lg">
    <h1 class="mb-5 text-2xl font-semibold sm:text-3xl">Tambah Gateway</h1>

    <form method="POST" action="{{ route('gateway.store') }}" class="card space-y-4">
        @csrf

        <p class="rounded-xl bg-canvas p-3 text-xs text-muted">Kode gateway dibuat otomatis (contoh: GW-001) dan tidak bisa diubah setelah disimpan.</p>

        <label class="block text-sm">
            <span class="mb-1.5 block font-medium">Nama gateway</span>
            <input name="name" value="{{ old('name') }}" required
                class="w-full rounded-xl border border-line bg-white px-3 py-2.5 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/15">
            @error('name')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror
        </label>

        <div class="flex justify-end gap-2 pt-2">
            <a href="{{ route('gateway.index') }}" class="btn text-muted hover:bg-canvas hover:text-ink">Batal</a>
            <button type="submit" class="btn btn-primary">Simpan</button>
        </div>
    </form>
</div>
@endsection
