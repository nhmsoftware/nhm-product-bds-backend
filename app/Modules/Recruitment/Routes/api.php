<?php

use App\Modules\Recruitment\Http\Controllers\RecruitmentPostController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Recruitment Module Routes
|--------------------------------------------------------------------------
| UC-126: Manage Recruitment Posts
*/

Route::prefix('v1/admin/recruitment/posts')->middleware(['auth:api', 'role:5'])->group(function () {
    Route::get('/', [RecruitmentPostController::class, 'index'])->name('admin.recruitment.posts.index');
    Route::post('/', [RecruitmentPostController::class, 'store'])->name('admin.recruitment.posts.store');
    Route::get('/{id}', [RecruitmentPostController::class, 'show'])->name('admin.recruitment.posts.show');
    Route::put('/{id}', [RecruitmentPostController::class, 'update'])->name('admin.recruitment.posts.update');
    Route::delete('/{id}', [RecruitmentPostController::class, 'destroy'])->name('admin.recruitment.posts.destroy');
});
