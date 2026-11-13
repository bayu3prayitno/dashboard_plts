<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'IoT Dashboard')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50">
    <div class="flex h-screen bg-gray-100">
        <!-- Sidebar Navigation -->
        <aside class="w-64 bg-gray-900 text-white shadow-lg overflow-y-auto">
            <div class="px-6 py-8">
                <h1 class="text-2xl font-bold">IoT Dashboard</h1>
                <p class="text-gray-400 text-sm">Monitoring & Management</p>
            </div>

            <nav class="mt-6">
                <a href="{{ route('dashboard.index') }}" class="flex items-center px-6 py-3 @if(Route::is('dashboard.index')) bg-gray-800 border-l-4 border-blue-500 @endif hover:bg-gray-800">
                    <svg class="w-5 h-5 mr-3" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"></path>
                    </svg>
                    <span>Dashboard</span>
                </a>

                <div class="mt-4">
                    <p class="px-6 py-2 text-gray-400 text-xs font-semibold uppercase">Management</p>

                    <a href="{{ route('devices.index') }}" class="flex items-center px-6 py-2 mt-2 @if(Route::is('devices.*')) bg-gray-800 border-l-4 border-blue-500 @endif hover:bg-gray-800">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2m0-10a2 2 0 012-2h2a2 2 0 012 2m0 0V7a2 2 0 012-2h2a2 2 0 012 2v10"></path>
                        </svg>
                        <span>Devices</span>
                    </a>

                    <a href="{{ route('alerts.index') }}" class="flex items-center px-6 py-2 mt-2 @if(Route::is('alerts.*')) bg-gray-800 border-l-4 border-blue-500 @endif hover:bg-gray-800">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                        </svg>
                        <span>Alerts</span>
                        <span class="ml-auto bg-red-500 text-white text-xs px-2 py-1 rounded-full">3</span>
                    </a>

                    <a href="{{ route('historical.index') }}" class="flex items-center px-6 py-2 mt-2 @if(Route::is('historical.*')) bg-gray-800 border-l-4 border-blue-500 @endif hover:bg-gray-800">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                        <span>Historical Data</span>
                    </a>

                    <a href="{{ route('gateway.index') }}" class="flex items-center px-6 py-2 mt-2 @if(Route::is('gateway.*')) bg-gray-800 border-l-4 border-blue-500 @endif hover:bg-gray-800">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                        </svg>
                        <span>Gateway</span>
                    </a>
                </div>

                <div class="mt-4">
                    <p class="px-6 py-2 text-gray-400 text-xs font-semibold uppercase">Configuration</p>

                    <a href="{{ route('settings.system') }}" class="flex items-center px-6 py-2 mt-2 @if(Route::is('settings.*')) bg-gray-800 border-l-4 border-blue-500 @endif hover:bg-gray-800">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                        <span>Settings</span>
                    </a>

                    <a href="{{ route('alerts.configuration') }}" class="flex items-center px-6 py-2 mt-2 hover:bg-gray-800">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path>
                        </svg>
                        <span>Alert Config</span>
                    </a>

                    <a href="{{ route('settings.auditLog') }}" class="flex items-center px-6 py-2 mt-2 hover:bg-gray-800">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <span>Audit Log</span>
                    </a>
                </div>
            </nav>

            <div class="mt-auto px-6 py-4">
                <button onclick="alert('Logout functionality akan ditambahkan setelah implementasi auth')" class="w-full text-left flex items-center px-4 py-2 hover:bg-gray-800 rounded text-gray-300">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                    </svg>
                    <span>Logout (Testing)</span>
                </button>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 overflow-auto">
            <!-- Top Navigation Bar -->
            <header class="bg-white shadow-sm sticky top-0 z-50">
                <div class="px-8 py-4 flex justify-between items-center">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-800">@yield('page-title', 'Dashboard')</h2>
                        <p class="text-sm text-gray-500">@yield('page-subtitle', '')</p>
                    </div>
                    <div class="flex items-center space-x-4">
                        <button class="p-2 hover:bg-gray-100 rounded-lg">
                            <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                            </svg>
                        </button>
                        <div class="h-8 w-8 bg-gradient-to-br from-blue-400 to-blue-600 rounded-full"></div>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <div class="px-8 py-6">
                @yield('content')
            </div>
        </main>
    </div>
</body>
</html>
