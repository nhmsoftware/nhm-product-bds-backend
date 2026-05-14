<?php

use App\Modules\Dashboard\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Dashboard Module Routes
|--------------------------------------------------------------------------
| UC-06: View Homepage
*/

Route::prefix('v1/dashboard')->middleware(['auth:api'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard.index');
});
