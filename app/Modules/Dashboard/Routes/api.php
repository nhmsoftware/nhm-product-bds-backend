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

    // UC-095: Manage Comment
    Route::get('/admin/comments', [\App\Modules\Dashboard\Http\Controllers\AdminCommentController::class, 'index'])->name('dashboard.admin.comments.index');
    Route::delete('/admin/comments/{id}', [\App\Modules\Dashboard\Http\Controllers\AdminCommentController::class, 'destroy'])->name('dashboard.admin.comments.destroy');
});
