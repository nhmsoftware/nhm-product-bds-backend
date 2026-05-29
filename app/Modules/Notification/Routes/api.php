<?php

use App\Modules\Notification\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Notification Module Routes
|--------------------------------------------------------------------------
|
| UC-130: View Notification
| UC-131: Mark Notification As Read
| Các route cho phép người dùng xem và quản lý thông báo cá nhân.
|
*/

Route::middleware('auth:api')->prefix('v1/notifications')->group(function () {
    // Xem danh sách thông báo cá nhân (UC-130 - Normal Flow bước 1-5)
    Route::get('/', [NotificationController::class, 'index'])->name('notifications.index');

    // Đánh dấu tất cả thông báo là đã đọc — phải khai báo TRƯỚC route có {id} (UC-131 – A4)
    Route::put('/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');

    // Xem chi tiết thông báo và tự động đánh dấu đã đọc (UC-131 – A5: Normal Flow bước 7-9)
    Route::get('/{id}', [NotificationController::class, 'show'])->name('notifications.show');

    // Đánh dấu thủ công một thông báo là đã đọc (UC-131 – Normal Flow bước 4-5)
    Route::put('/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
});
