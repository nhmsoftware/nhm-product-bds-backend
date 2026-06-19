<?php

use App\Modules\Area\Http\Controllers\AdminLotDepositRequestController;
use App\Modules\Area\Http\Controllers\AreaController;
use App\Modules\Area\Http\Controllers\AdminAreaController;
use App\Modules\Area\Http\Controllers\LotDepositRequestController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Area Module Routes
|--------------------------------------------------------------------------
|
| UC-077: View Assigned Land Area List
|
*/

Route::middleware(['auth:api', 'onboarding'])->prefix('v1')->group(function () {
    Route::get('/areas', [AreaController::class, 'index'])->name('index');
    Route::get('/areas/search', [AreaController::class, 'search'])->name('areas.search');
    Route::get('/areas/{id}/inventory-map', [AreaController::class, 'inventoryMap'])->name('areas.inventory_map');
    Route::get('/lots/{id}', [AreaController::class, 'lotDetail'])->name('lots.detail');
    Route::post('/areas/{id}/comments', [AreaController::class, 'addAreaComment'])->name('areas.comment');
    Route::post('/lots/{id}/lock', [AreaController::class, 'requestLockLot'])->name('lots.lock');
    Route::post('/lots/{lot}/deposit-requests', [LotDepositRequestController::class, 'store'])->name('lots.deposit-requests.store');
});

Route::middleware(['auth:api'])->prefix('v1/admin/lots')->group(function () {
    Route::patch('/{id}/lock', [AdminAreaController::class, 'lockLot'])->name('admin.lots.lock');
});

Route::middleware(['auth:api'])->prefix('v1/admin/deposit-requests')->group(function () {
    Route::get('/', [AdminLotDepositRequestController::class, 'index'])->name('admin.deposit-requests.index');
    Route::get('/{id}', [AdminLotDepositRequestController::class, 'show'])->name('admin.deposit-requests.show');
    Route::patch('/{id}/approve', [AdminLotDepositRequestController::class, 'approve'])->name('admin.deposit-requests.approve');
    Route::patch('/{id}/reject', [AdminLotDepositRequestController::class, 'reject'])->name('admin.deposit-requests.reject');
    Route::patch('/{id}/confirm-transaction', [AdminLotDepositRequestController::class, 'confirmTransaction'])->name('admin.deposit-requests.confirm-transaction');
});
