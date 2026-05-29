<?php

use App\Modules\Auth\Models\Enums\UserRole;
use App\Modules\Dashboard\Http\Controllers\AdminCommentController;
use App\Modules\Dashboard\Http\Controllers\CompanyDashboardController;
use App\Modules\Dashboard\Http\Controllers\DashboardController;
use App\Modules\Dashboard\Http\Controllers\EmployeeReportController;
use App\Modules\Dashboard\Http\Controllers\RevenueReportController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Dashboard Module Routes
|--------------------------------------------------------------------------
| UC-06: View Homepage
*/

Route::prefix('v1/dashboard')->middleware(['auth:api'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard.index');

    // UC-109: View Employee Reports (Director)
    Route::get('/employee-reports', [EmployeeReportController::class, 'index'])
        ->middleware('role:' . UserRole::DIRECTOR->value)
        ->name('dashboard.employee-reports.index');

    // UC-110: View Department Reports (Director)
    Route::get('/department-reports', [EmployeeReportController::class, 'departmentReports'])
        ->middleware('role:' . UserRole::DIRECTOR->value)
        ->name('dashboard.department-reports.index');

    // UC-111: View Company Dashboard (CEO)
    Route::get('/company', [CompanyDashboardController::class, 'index'])
        ->middleware('role:' . UserRole::CEO->value)
        ->name('dashboard.company.index');

    // UC-112: View Revenue Reports (CEO)
    Route::get('/revenue-reports', [RevenueReportController::class, 'index'])
        ->middleware('role:' . UserRole::CEO->value)
        ->name('dashboard.revenue-reports.index');

    // UC-095: Manage Comment
    Route::get('/admin/comments', [AdminCommentController::class, 'index'])->name('dashboard.admin.comments.index');
    Route::delete('/admin/comments/{id}', [AdminCommentController::class, 'destroy'])->name('dashboard.admin.comments.destroy');
});
