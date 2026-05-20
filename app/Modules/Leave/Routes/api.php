<?php

use App\Modules\Leave\Http\Controllers\LeaveController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Leave Module Routes
|--------------------------------------------------------------------------
|
| UC-043: Request Leave
|
*/

Route::middleware('auth:api')->prefix('v1/leave')->group(function () {
    // Gửi yêu cầu xin nghỉ phép mới cho nhân viên
    Route::post('/requests', [LeaveController::class, 'store'])->name('leave.requests.store');

    // Xem danh sách yêu cầu nghỉ phép của phòng ban (cho Team Leader) (UC-046)
    Route::get('/requests', [LeaveController::class, 'index'])->name('leave.requests.index');

    // Xem lịch sử yêu cầu nghỉ phép của nhân viên (UC-044)
    Route::get('/history', [LeaveController::class, 'history'])->name('leave.requests.history');

    // Hủy yêu cầu nghỉ phép (UC-045)
    Route::put('/requests/{id}/cancel', [LeaveController::class, 'cancel'])->name('leave.requests.cancel');

    // Phê duyệt yêu cầu nghỉ phép của nhân viên (cho Team Leader) (UC-047)
    Route::put('/requests/{id}/approve', [LeaveController::class, 'approve'])->name('leave.requests.approve');

    // Từ chối yêu cầu nghỉ phép của nhân viên (cho Team Leader) (UC-048)
    Route::put('/requests/{id}/reject', [LeaveController::class, 'reject'])->name('leave.requests.reject');
});
