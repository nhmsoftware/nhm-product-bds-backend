<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Auth\Http\Controllers\AuthController;

Route::prefix('v1/auth')->group(function () {
    // UC-001: Đăng ký tài khoản mới
    Route::post('register', [AuthController::class, 'register'])->name('auth.register');
    // UC-002: Đăng nhập hệ thống
    Route::post('login', [AuthController::class, 'login'])->name('auth.login');
    // UC-003: Quên mật khẩu
    Route::post('forgot-password', [AuthController::class, 'forgotPassword'])->name('auth.forgot-password');
    Route::post('verify-otp', [AuthController::class, 'verifyOtp'])->name('auth.verify-otp');
    Route::post('reset-password', [AuthController::class, 'resetPassword'])->name('auth.reset-password');

    // Các route yêu cầu đăng nhập
    Route::middleware('auth:api')->group(function () {
        Route::post('logout', [AuthController::class, 'logout'])->name('auth.logout');
        // UC-030: Xem hồ sơ cá nhân
        Route::get('profile', [AuthController::class, 'profile'])->name('auth.profile');
        // UC-031: Cập nhật hồ sơ cá nhân
        Route::put('profile', [AuthController::class, 'updateProfile'])->name('auth.updateProfile');
        // UC-032: Thay đổi mật khẩu
        Route::put('change-password', [AuthController::class, 'changePassword'])->name('auth.changePassword');
        // UC-033: Xem hồ sơ cá nhân nhân sự
        Route::get('employee-profile', [AuthController::class, 'employeeProfile'])->name('auth.employeeProfile');
        // UC-034: Cập nhật hồ sơ cá nhân nhân sự
        Route::put('employee-profile', [AuthController::class, 'updateEmployeeProfile'])->name('auth.updateEmployeeProfile');
        // UC-035: Tải lên tài liệu hồ sơ nhân sự
        Route::post('employee-profile/documents', [AuthController::class, 'uploadEmployeeDocument'])->name('auth.uploadEmployeeDocument');
    });
});

