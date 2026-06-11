<?php

use App\Modules\EmployeeReferral\Http\Controllers\ReferralHistoryController;
use App\Modules\EmployeeReferral\Http\Controllers\ReferralQrController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Employee Referral Module Routes
|--------------------------------------------------------------------------
*/

// Public endpoint cho phép ghi nhận lượt quét QR
Route::post('v1/referrals/scan', [ReferralHistoryController::class, 'recordScan'])->name('referrals.scan');

// Public endpoint điều hướng người quét QR sang App Store hoặc CH Play theo thiết bị
Route::get('v1/referrals/open', [ReferralQrController::class, 'openAppDownload'])->name('referrals.open');

Route::middleware('auth:api')->prefix('v1/employee-referrals')->group(function () {
    // Xem mã QR tuyển dụng cá nhân (UC-098)
    Route::get('/recruitment-qr', [\App\Modules\EmployeeReferral\Http\Controllers\ReferralQrController::class, 'getRecruitmentQr'])->name('employee-referrals.recruitment-qr');

    // Xem mã QR giới thiệu khách hàng (UC-100)
    Route::get('/customer-qr', [\App\Modules\EmployeeReferral\Http\Controllers\ReferralQrController::class, 'getCustomerQr'])->name('employee-referrals.customer-qr');

    // Xem danh sách lịch sử referral (UC-096)
    Route::get('/history', [ReferralHistoryController::class, 'history'])->name('employee-referrals.history');
    
    // Xem chi tiết bản ghi referral (UC-096)
    Route::get('/history/{id}', [ReferralHistoryController::class, 'detail'])->name('employee-referrals.detail');

    // Xem danh sách hoa hồng referral (UC-097)
    Route::get('/commissions', [\App\Modules\EmployeeReferral\Http\Controllers\ReferralCommissionController::class, 'index'])->name('employee-referrals.commissions');

    // Xem báo cáo hoa hồng referral (UC-102)
    Route::get('/reports/commissions', [\App\Modules\EmployeeReferral\Http\Controllers\ReferralCommissionController::class, 'report'])->name('employee-referrals.reports.commissions');
    
    // Xem chi tiết hoa hồng referral (UC-097)
    Route::get('/commissions/{id}', [\App\Modules\EmployeeReferral\Http\Controllers\ReferralCommissionController::class, 'detail'])->name('employee-referrals.commissions.detail');

    // Xem cấu hình hoa hồng referral (UC-103)
    Route::get('/commission-configs', [\App\Modules\EmployeeReferral\Http\Controllers\ReferralCommissionConfigController::class, 'index'])->name('employee-referrals.commission-configs.index');
    
    // Cập nhật cấu hình hoa hồng referral (UC-104)
    Route::put('/commission-configs', [\App\Modules\EmployeeReferral\Http\Controllers\ReferralCommissionConfigController::class, 'update'])->name('employee-referrals.commission-configs.update');
});
