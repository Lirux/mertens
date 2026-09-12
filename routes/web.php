<?php

use App\Http\Controllers\Web\AssetController;
use App\Http\Controllers\Web\AssetMaintenanceController;
use App\Http\Controllers\Web\DashboardController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::pattern('asset', '[0-9a-fA-F]{24}');
    Route::resource('assets', AssetController::class);
    Route::get('assets/{asset}/maintenance', [AssetMaintenanceController::class, 'edit'])
        ->name('assets.maintenance.edit');
    Route::put('assets/{asset}/maintenance', [AssetMaintenanceController::class, 'update'])
        ->name('assets.maintenance.update');
});

require __DIR__.'/settings.php';
