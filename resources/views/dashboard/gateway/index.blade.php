@extends('layouts.app')

@section('title', 'Manajemen Gateway - IoT Dashboard')
@section('page-title', 'Manajemen Gateway')
@section('page-subtitle', 'Monitor kesehatan dan status infrastruktur Gateway')

@section('content')
<div class="space-y-6">
    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-gray-600 text-sm">Total Gateway</p>
            <p class="text-3xl font-bold text-gray-800">{{ count($gateways) }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-gray-600 text-sm">Gateway Aktif</p>
            <p class="text-3xl font-bold text-green-600">{{ count(array_filter($gateways, fn($g) => $g['status'] === 'connected')) }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-gray-600 text-sm">Total Node Terhubung</p>
            <p class="text-3xl font-bold text-blue-600">{{ array_sum(array_column($gateways, 'connected_nodes')) }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-gray-600 text-sm">SD Card Kesehatan</p>
            <p class="text-3xl font-bold text-yellow-600">{{ count(array_filter($gateways, fn($g) => $g['sd_card_status'] === 'healthy')) }}/{{ count($gateways) }}</p>
        </div>
    </div>

    <!-- Gateways Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Nama Gateway</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Lokasi</th>
                    <th class="px-6 py-3 text-center text-sm font-semibold text-gray-700">Status Koneksi</th>
                    <th class="px-6 py-3 text-center text-sm font-semibold text-gray-700">MQTT</th>
                    <th class="px-6 py-3 text-center text-sm font-semibold text-gray-700">Internet</th>
                    <th class="px-6 py-3 text-center text-sm font-semibold text-gray-700">Node Terhubung</th>
                    <th class="px-6 py-3 text-center text-sm font-semibold text-gray-700">SD Card</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @foreach($gateways as $gateway)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 text-sm font-semibold text-gray-800">{{ $gateway['name'] }}</td>
                    <td class="px-6 py-4 text-sm text-gray-600">{{ $gateway['location'] }}</td>
                    <td class="px-6 py-4 text-center">
                        @if($gateway['status'] === 'connected')
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                                <span class="inline-block w-2 h-2 bg-green-600 rounded-full mr-2 animate-pulse"></span>
                                Terhubung
                            </span>
                        @else
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800">
                                <span class="inline-block w-2 h-2 bg-red-600 rounded-full mr-2"></span>
                                Terputus
                            </span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-center">
                        @if($gateway['mqtt_status'] === 'connected')
                            <span class="inline-block px-2 py-1 text-xs font-medium bg-green-100 text-green-800 rounded">OK</span>
                        @else
                            <span class="inline-block px-2 py-1 text-xs font-medium bg-red-100 text-red-800 rounded">ERROR</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-center">
                        @if($gateway['internet_status'] === 'connected')
                            <span class="inline-block px-2 py-1 text-xs font-medium bg-green-100 text-green-800 rounded">OK</span>
                        @else
                            <span class="inline-block px-2 py-1 text-xs font-medium bg-red-100 text-red-800 rounded">ERROR</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-center text-sm font-semibold text-gray-800">{{ $gateway['connected_nodes'] }}</td>
                    <td class="px-6 py-4 text-center">
                        <span class="text-xs text-gray-600">{{ $gateway['sd_card_usage'] }}</span>
                        @if($gateway['sd_card_status'] === 'healthy')
                            <span class="inline-block ml-2 px-2 py-1 text-xs font-medium bg-green-100 text-green-800 rounded">✓</span>
                        @else
                            <span class="inline-block ml-2 px-2 py-1 text-xs font-medium bg-yellow-100 text-yellow-800 rounded">!</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-sm">
                        <a href="{{ route('gateway.show', $gateway['id']) }}" class="text-blue-600 hover:text-blue-800 font-semibold">Detail</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- Infrastructure Health Summary -->
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Ringkasan Kesehatan Infrastruktur</h3>
        <div class="space-y-3">
            @foreach($gateways as $gateway)
            <div class="p-4 border border-gray-200 rounded-lg">
                <div class="flex items-center justify-between mb-3">
                    <span class="font-semibold text-gray-800">{{ $gateway['name'] }} - {{ $gateway['location'] }}</span>
                    <span class="text-xs text-gray-500">Update: {{ $gateway['last_communication'] }}</span>
                </div>
                <div class="grid grid-cols-4 gap-4">
                    <div>
                        <p class="text-xs text-gray-600">Gateway</p>
                        <span class="inline-block px-2 py-1 text-xs font-medium rounded @if($gateway['status'] === 'connected') bg-green-100 text-green-800 @else bg-red-100 text-red-800 @endif">
                            {{ $gateway['status'] === 'connected' ? 'OK' : 'ERROR' }}
                        </span>
                    </div>
                    <div>
                        <p class="text-xs text-gray-600">MQTT Broker</p>
                        <span class="inline-block px-2 py-1 text-xs font-medium rounded @if($gateway['mqtt_status'] === 'connected') bg-green-100 text-green-800 @else bg-red-100 text-red-800 @endif">
                            {{ $gateway['mqtt_status'] === 'connected' ? 'OK' : 'ERROR' }}
                        </span>
                    </div>
                    <div>
                        <p class="text-xs text-gray-600">Internet</p>
                        <span class="inline-block px-2 py-1 text-xs font-medium rounded @if($gateway['internet_status'] === 'connected') bg-green-100 text-green-800 @else bg-red-100 text-red-800 @endif">
                            {{ $gateway['internet_status'] === 'connected' ? 'OK' : 'ERROR' }}
                        </span>
                    </div>
                    <div>
                        <p class="text-xs text-gray-600">SD Card</p>
                        <span class="inline-block px-2 py-1 text-xs font-medium rounded @if($gateway['sd_card_status'] === 'healthy') bg-green-100 text-green-800 @else bg-yellow-100 text-yellow-800 @endif">
                            {{ $gateway['sd_card_usage'] }}
                        </span>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
