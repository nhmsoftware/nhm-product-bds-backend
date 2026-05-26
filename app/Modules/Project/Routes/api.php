<?php

use App\Modules\Project\Http\Controllers\PublicProjectController;
use App\Modules\Project\Http\Controllers\AdminProjectController;
use App\Modules\Project\Http\Controllers\AdminProjectAssignmentController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Project Module Routes
|--------------------------------------------------------------------------
|
| UC-13 View Public Project List
| Actor: Guest, Customer
|
*/

Route::prefix('v1/public/projects')->group(function () {
    Route::get('/', [PublicProjectController::class, 'index'])->name('project.public.index');
    Route::get('/search', [PublicProjectController::class, 'search'])->name('project.public.search');
    Route::get('/{id}', [PublicProjectController::class, 'show'])->name('project.public.show');
    Route::get('/{id}/brochure', [PublicProjectController::class, 'downloadBrochure'])->name('project.public.brochure');
    Route::get('/{id}/hotline', [PublicProjectController::class, 'getHotline'])->name('project.public.hotline');
});

/*
|--------------------------------------------------------------------------
| UC-085 Manage Projects
| Actor: Super Admin, General Director
|--------------------------------------------------------------------------
*/
Route::prefix('v1/admin/projects')->middleware(['auth:api'])->group(function () {
    Route::get('/', [AdminProjectController::class, 'index'])->name('project.admin.index');
    Route::post('/', [AdminProjectController::class, 'store'])->name('project.admin.store');
    Route::post('/bulk-create', [AdminProjectController::class, 'bulkCreate'])->name('project.admin.bulk_create');
    Route::get('/{id}', [AdminProjectController::class, 'show'])->name('project.admin.show');
    Route::put('/{id}', [AdminProjectController::class, 'update'])->name('project.admin.update');
    Route::patch('/{id}/lock', [AdminProjectController::class, 'lock'])->name('project.admin.lock');

    Route::post('/{project}/assignments', [AdminProjectAssignmentController::class, 'store'])->name('project.admin.assignments.store');
});
