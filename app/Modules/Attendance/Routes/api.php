<?php

use App\Modules\Attendance\Http\Controllers\AttendanceController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Attendance Module Routes
|--------------------------------------------------------------------------
|
| UC-036: Check-in Office
| UC-037: Check-out Office
|
*/

Route::middleware('auth:api')->prefix('v1/attendance')->group(function () {
    // Thực hiện check-in vào ca
    Route::post('/check-in', [AttendanceController::class, 'checkIn'])->name('attendance.check-in');

    // Thực hiện check-out kết thúc ca
    Route::post('/check-out', [AttendanceController::class, 'checkOut'])->name('attendance.check-out');
    
    // Lấy trạng thái chấm công của hôm nay
    Route::get('/today', [AttendanceController::class, 'todayStatus'])->name('attendance.today-status');
});
