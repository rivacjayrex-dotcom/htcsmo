<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\RequisitionController;
use App\Http\Controllers\Api\V1\SupplyController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::post('/auth/tokens', [AuthController::class, 'store'])
        ->middleware('throttle:api-login')->name('auth.store');

    Route::middleware(['auth:sanctum', 'api.active', 'throttle:api-v1'])->group(function () {
        Route::get('/me', [AuthController::class, 'show'])->name('me');
        Route::delete('/auth/token', [AuthController::class, 'destroy'])->name('auth.destroy');

        Route::get('/supplies', [SupplyController::class, 'index'])
            ->middleware('abilities:supplies:read')->name('supplies.index');
        Route::get('/supplies/{supply}', [SupplyController::class, 'show'])->whereNumber('supply')
            ->middleware('abilities:supplies:read')->name('supplies.show');
        Route::patch('/supplies/{supply}', [SupplyController::class, 'update'])->whereNumber('supply')
            ->middleware('abilities:supplies:write')->name('supplies.update');

        Route::get('/requisitions', [RequisitionController::class, 'index'])
            ->middleware('abilities:requisitions:read')->name('requisitions.index');
        Route::get('/requisitions/{id}', [RequisitionController::class, 'show'])->whereNumber('id')
            ->middleware('abilities:requisitions:read')->name('requisitions.show');
        Route::post('/requisitions', [RequisitionController::class, 'store'])
            ->middleware(['abilities:requisitions:write', 'throttle:api-submissions'])->name('requisitions.store');
    });
});
