<?php

use App\Modules\DepartmentTransfer\Http\Controllers\DepartmentTransferController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('auth:api')->group(function () {
    Route::post('/department-transfers', [DepartmentTransferController::class, 'store']);
    Route::get('/department-transfers', [DepartmentTransferController::class, 'index']);
    Route::put('/department-transfers/{id}/approve', [DepartmentTransferController::class, 'approve'])->name('department-transfers.approve');
    Route::put('/department-transfers/{id}/reject', [DepartmentTransferController::class, 'reject'])->name('department-transfers.reject');
});

