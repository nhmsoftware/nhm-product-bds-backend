<?php

use App\Modules\Area\Http\Controllers\AreaController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Area Module Routes
|--------------------------------------------------------------------------
|
| UC-077: View Assigned Land Area List
|
*/

Route::middleware('auth:api')->prefix('v1')->group(function () {
    Route::get('/areas', [AreaController::class, 'index'])->name('index');
    Route::get('/areas/search', [AreaController::class, 'search'])->name('areas.search');
    Route::get('/areas/{id}/inventory-map', [AreaController::class, 'inventoryMap'])->name('areas.inventory_map');
    Route::get('/lots/{id}', [AreaController::class, 'lotDetail'])->name('lots.detail');
    Route::post('/lots/{id}/comments', [AreaController::class, 'addLotComment'])->name('lots.comment');
    Route::post('/lots/{id}/lock', [AreaController::class, 'requestLockLot'])->name('lots.lock');
});

