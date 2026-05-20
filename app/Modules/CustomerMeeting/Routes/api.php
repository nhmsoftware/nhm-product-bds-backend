<?php

use App\Modules\CustomerMeeting\Http\Controllers\CustomerMeetingController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Customer Meeting Module Routes
|--------------------------------------------------------------------------
|
| UC-038: Check-in Meet Customer
|
*/

Route::middleware('auth:api')->prefix('v1/customer-meetings')->group(function () {
    // Check-in hoạt động gặp khách hàng
    Route::post('/check-in', [CustomerMeetingController::class, 'checkIn'])->name('customer-meetings.check-in');

    // Lấy danh sách hoạt động gần đây
    Route::get('/recent', [CustomerMeetingController::class, 'recent'])->name('customer-meetings.recent');

    // Xem chi tiết hoạt động gặp khách hàng (UC-042)
    Route::get('/{id}', [CustomerMeetingController::class, 'show'])->name('customer-meetings.show');
});
