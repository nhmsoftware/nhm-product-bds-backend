<?php

use App\Modules\SiteTour\Http\Controllers\SiteTourController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Site Tour Module Routes
|--------------------------------------------------------------------------
|
| UC-039: Check-in Site Tour
|
*/

Route::middleware('auth:api')->prefix('v1/site-tours')->group(function () {
    // Check-in hoạt động dẫn khách tham quan
    Route::post('/check-in', [SiteTourController::class, 'checkIn'])->name('site-tours.check-in');

    // Lấy danh sách hoạt động gần đây
    Route::get('/recent', [SiteTourController::class, 'recent'])->name('site-tours.recent');

    // Xem lịch sử dẫn khách tham quan (UC-041)
    Route::get('/history', [SiteTourController::class, 'history'])->name('site-tours.history');
});
