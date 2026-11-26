@props(['status'])
@php
    $map = [
        'online' => ['Online', 'bg-emerald-50 text-emerald-700', 'bg-emerald-500'],
        'offline' => ['Offline', 'bg-red-50 text-red-700', 'bg-red-500'],
        'nonaktif' => ['Nonaktif', 'bg-slate-100 text-slate-600', 'bg-slate-400'],
        'aktif' => ['Aktif', 'bg-emerald-50 text-emerald-700', 'bg-emerald-500'],
        'baru' => ['Baru', 'bg-red-50 text-red-700', 'bg-red-500'],
        'ditangani' => ['Ditangani', 'bg-amber-50 text-amber-800', 'bg-amber-500'],
        'selesai' => ['Selesai', 'bg-emerald-50 text-emerald-700', 'bg-emerald-500'],
        'connected' => ['Terhubung', 'bg-emerald-50 text-emerald-700', 'bg-emerald-500'],
        'disconnected' => ['Terputus', 'bg-red-50 text-red-700', 'bg-red-500'],
        // Kualitas sinyal LoRa (DashboardController::linkQuality)
        'baik' => ['Baik', 'bg-emerald-50 text-emerald-700', 'bg-emerald-500'],
        'sedang' => ['Sedang', 'bg-amber-50 text-amber-800', 'bg-amber-500'],
        'lemah' => ['Lemah', 'bg-red-50 text-red-700', 'bg-red-500'],
    ];
    [$label, $tone, $dot] = $map[$status] ?? $map['nonaktif'];
@endphp
<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium whitespace-nowrap $tone"]) }}>
    <span class="size-1.5 rounded-full {{ $dot }}"></span>{{ $label }}
</span>
