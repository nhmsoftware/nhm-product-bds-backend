<?php

use App\Modules\Planning\Http\Controllers\PlanningController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Planning Module Routes
|--------------------------------------------------------------------------
| UC-019: View Planning List
| UC-020: View Planning Detail (Future)
*/

Route::group(['prefix' => 'v1/admin/planning', 'middleware' => ['auth:api']], function () {
    Route::post('/check-lot', [PlanningController::class, 'checkLot'])->name('planning.admin.check_lot');
});

Route::prefix('v1/public/plannings')->group(function () {
    Route::get('/', [PlanningController::class, 'index'])->name('public.plannings.index');
    Route::get('/cities', [PlanningController::class, 'getCities'])->name('public.plannings.cities');
    Route::get('/search', [PlanningController::class, 'search'])->name('public.plannings.search');
    Route::get('/{id}', [PlanningController::class, 'show'])->name('public.plannings.show');
});

Route::middleware('auth:api')->prefix('v1/plannings')->group(function () {
    Route::get('/{id}/download', [PlanningController::class, 'download'])->name('plannings.download');
});
