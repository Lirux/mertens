<?php

use App\Http\Controllers\Api\V1\AssetController;
use Illuminate\Support\Facades\Route;

Route::pattern('asset', '[0-9a-fA-F]{24}');

Route::prefix('v1')
    ->middleware(['auth:sanctum', 'throttle:asset-api'])
    ->group(function (): void {
        Route::get('assets', [AssetController::class, 'index'])
            ->middleware('abilities:assets:read')
            ->name('api.v1.assets.index');
        Route::post('assets', [AssetController::class, 'store'])
            ->middleware('abilities:assets:write')
            ->name('api.v1.assets.store');
        Route::get('assets/{asset}', [AssetController::class, 'show'])
            ->middleware('abilities:assets:read')
            ->name('api.v1.assets.show');
        Route::put('assets/{asset}', [AssetController::class, 'update'])
            ->middleware('abilities:assets:write')
            ->name('api.v1.assets.update');
        Route::delete('assets/{asset}', [AssetController::class, 'destroy'])
            ->middleware('abilities:assets:write')
            ->name('api.v1.assets.destroy');
    });
