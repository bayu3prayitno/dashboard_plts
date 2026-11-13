<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeviceController;
use App\Http\Controllers\AlertController;
use App\Http\Controllers\HistoricalDataController;
use App\Http\Controllers\GatewayController;
use App\Http\Controllers\SettingsController;

// Redirect root to dashboard
Route::get('/', function () {
    return redirect('/dashboard');
});

// Dashboard Routes (for testing - uncomment ['auth'] middleware after implementing login)
Route::group([], function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');

    // Devices Management
    Route::prefix('devices')->name('devices.')->group(function () {
        Route::get('/', [DeviceController::class, 'index'])->name('index');
        Route::get('/create', [DeviceController::class, 'create'])->name('create');
        Route::post('/', [DeviceController::class, 'store'])->name('store');
        Route::get('/{id}', [DeviceController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [DeviceController::class, 'edit'])->name('edit');
        Route::put('/{id}', [DeviceController::class, 'update'])->name('update');
        Route::delete('/{id}', [DeviceController::class, 'delete'])->name('delete');
    });

    // Alerts Management
    Route::prefix('alerts')->name('alerts.')->group(function () {
        Route::get('/', [AlertController::class, 'index'])->name('index');
        Route::post('/{id}/status', [AlertController::class, 'updateStatus'])->name('updateStatus');
        Route::get('/configuration', [AlertController::class, 'configuration'])->name('configuration');
        Route::post('/threshold/{id}', [AlertController::class, 'updateThreshold'])->name('updateThreshold');
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
        Route::get('/{id}', [GatewayController::class, 'show'])->name('show');
    });

    // Settings
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/', [SettingsController::class, 'index'])->name('index');
        Route::get('/users', [SettingsController::class, 'users'])->name('users');
        Route::get('/system', [SettingsController::class, 'systemSettings'])->name('system');
        Route::post('/system', [SettingsController::class, 'updateSystemSettings'])->name('systemUpdate');
        Route::get('/audit-log', [SettingsController::class, 'auditLog'])->name('auditLog');
        Route::get('/scope', [SettingsController::class, 'scopeAssignment'])->name('scope');
        Route::post('/scope/{id}', [SettingsController::class, 'updateScope'])->name('scopeUpdate');
    });
});
