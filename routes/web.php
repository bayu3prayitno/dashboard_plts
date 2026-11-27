<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeviceController;
use App\Http\Controllers\AlertController;
use App\Http\Controllers\HistoricalDataController;
use App\Http\Controllers\GatewayController;
use App\Http\Controllers\Api\GatewayApiController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Auth\LoginController;

// Autentikasi
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])
    ->middleware('throttle:4,1')
    ->name('login.store');
});
Route::post('/logout', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');

// Redirect root to dashboard
Route::get('/', function () {
    return redirect('/dashboard');
});

// Semua halaman dashboard wajib login; tamu diarahkan ke /login (route bernama 'login')
Route::middleware('auth')->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');

    // Profil pengguna yang sedang login (dibuka dari kartu pengguna di sidebar)
    Route::get('/profil', [ProfileController::class, 'show'])->name('profile.show');
    Route::put('/profil', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profil/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

    // Devices Management
    Route::prefix('devices')->name('devices.')->group(function () {
        Route::get('/', [DeviceController::class, 'index'])->name('index');
        Route::get('/create', [DeviceController::class, 'create'])->name('create');
        Route::post('/', [DeviceController::class, 'store'])->name('store');
        Route::get('/{device}', [DeviceController::class, 'show'])->name('show');
        Route::get('/{device}/edit', [DeviceController::class, 'edit'])->name('edit');
        Route::put('/{device}', [DeviceController::class, 'update'])->name('update');
        Route::delete('/{device}', [DeviceController::class, 'delete'])->name('delete');
    });

    // Alerts Management
    Route::prefix('alerts')->name('alerts.')->group(function () {
        Route::get('/', [AlertController::class, 'index'])->name('index');
        Route::post('/{alert}/status', [AlertController::class, 'updateStatus'])->name('updateStatus');
        Route::get('/configuration', [AlertController::class, 'configuration'])->name('configuration');
        Route::post('/configuration', [AlertController::class, 'saveConfiguration'])->name('configuration.update');
    });

    // Historical Data & Reports
    Route::prefix('historical')->name('historical.')->group(function () {
        Route::get('/', [HistoricalDataController::class, 'index'])->name('index');
        Route::post('/filter', [HistoricalDataController::class, 'filter'])->name('filter');
        Route::get('/export-csv', [HistoricalDataController::class, 'exportCsv'])->name('exportCsv');
        Route::get('/chart-data', [HistoricalDataController::class, 'getChartData'])->name('chartData');
    });

    // Gateway Management
    Route::prefix('gateway')->name('gateway.')->group(function () {
        Route::get('/', [GatewayController::class, 'index'])->name('index');
        Route::get('/create', [GatewayController::class, 'create'])->name('create');
        Route::post('/', [GatewayController::class, 'store'])->name('store');
        Route::get('/{gateway}', [GatewayController::class, 'show'])->name('show');
        Route::get('/{gateway}/edit', [GatewayController::class, 'edit'])->name('edit');
        Route::put('/{gateway}', [GatewayController::class, 'update'])->name('update');
        Route::delete('/{gateway}', [GatewayController::class, 'delete'])->name('delete');
    });

    // API JSON gateway (login berbasis sesi; belum untuk perangkat eksternal)
    Route::prefix('api/gateways')->name('api.gateways.')->group(function () {
        Route::get('/', [GatewayApiController::class, 'index'])->name('index');
        Route::post('/', [GatewayApiController::class, 'store'])->name('store');
        Route::get('/{gateway}', [GatewayApiController::class, 'show'])->name('show');
        Route::put('/{gateway}', [GatewayApiController::class, 'update'])->name('update');
        Route::delete('/{gateway}', [GatewayApiController::class, 'destroy'])->name('destroy');
    });

    // API untuk live data (polling + SSE)
    Route::prefix('api/live')->name('api.live.')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'liveData'])->name('dashboard');
        Route::get('/alerts', [AlertController::class, 'liveStream'])->name('alerts');
        Route::get('/devices-status', [DeviceController::class, 'liveStatus'])->name('devices');
    });

    // Settings
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/', [SettingsController::class, 'index'])->name('index');
        Route::get('/users', [SettingsController::class, 'users'])->name('users');
        Route::get('/system', [SettingsController::class, 'systemSettings'])->name('system');
        Route::post('/system', [SettingsController::class, 'updateSystemSettings'])->name('systemUpdate');
        Route::post('/system/mqtt', [SettingsController::class, 'updateMqttSettings'])->name('mqttUpdate');
        Route::post('/system/reset-data', [SettingsController::class, 'resetData'])
            ->middleware('throttle:5,1')
            ->name('resetData');
        Route::get('/audit-log', [SettingsController::class, 'auditLog'])->name('auditLog');
        Route::get('/scope', [SettingsController::class, 'scopeAssignment'])->name('scope');
        Route::post('/scope/{id}', [SettingsController::class, 'updateScope'])->name('scopeUpdate');
    });
});
