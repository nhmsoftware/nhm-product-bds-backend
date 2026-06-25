<?php

use App\Modules\Project\Http\Controllers\PublicProjectController;
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
    Route::get('/types', [PublicProjectController::class, 'types'])->name('project.public.types');
    Route::get('/search', [PublicProjectController::class, 'search'])->name('project.public.search');
    Route::get('/{id}', [PublicProjectController::class, 'show'])->name('project.public.show');
    Route::get('/{id}/brochure', [PublicProjectController::class, 'downloadBrochure'])->name('project.public.brochure');
    Route::get('/{id}/hotline', [PublicProjectController::class, 'getHotline'])->name('project.public.hotline');
});
