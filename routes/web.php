<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\RouterController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ConnectedDeviceController;


Route::get('/api/bandwidth', [App\Http\Controllers\DashboardController::class, 'bandwidth']);
// Delete a connected device
Route::delete(
    '/routers/{router}/devices/{device}',
    [ConnectedDeviceController::class, 'destroy']
)->name('routers.devices.destroy');

// Redirect root to dashboard
Route::get('/', function () {
    return redirect()->route('dashboard');
});

// Dashboard
Route::get('/dashboard', [DashboardController::class, 'index'])
     ->name('dashboard');

// Routers resource (index, create, store, show, edit, update, destroy)
Route::resource('routers', RouterController::class);

// “Check Now” action
Route::post('/routers/{router}/check-now', [RouterController::class, 'checkNow'])
     ->name('routers.check-now');

// 7-day bandwidth data (AJAX)
Route::get('routers/{router}/bw-data', [RouterController::class, 'bwData'])
     ->name('routers.bw-data');

// Real-time traffic data (AJAX)
Route::get('routers/{router}/realtime-data', [RouterController::class, 'realtimeData'])
     ->name('routers.realtime-data');

// Report routes (router status, bandwidth usage, connected devices, device details)
Route::prefix('reports')->group(function () {
    Route::get('/router-status',    [ReportController::class, 'routerStatus'])
         ->name('reports.router-status');
    Route::get('/bandwidth-usage',  [ReportController::class, 'bandwidthUsage'])
         ->name('reports.bandwidth-usage');
    Route::get('/connected-devices',[ReportController::class, 'connectedDevices'])
         ->name('reports.connected-devices');
    Route::get('/device/{id}',      [ReportController::class, 'deviceDetails'])
         ->name('reports.device-details');
});


// api versions


