<?php

use App\Modules\News\Http\Controllers\NewsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| News Module Routes
|--------------------------------------------------------------------------
| UC-08: View News
*/

Route::prefix('v1/news')->group(function () {
    Route::get('/', [NewsController::class, 'index'])->name('news.index');
    Route::get('/search', [NewsController::class, 'search'])->name('news.search');
    Route::get('/{idOrSlug}', [NewsController::class, 'show'])->name('news.show');

    Route::middleware('auth:api')->group(function () {
        Route::post('/{id}/like', [NewsController::class, 'like'])->name('news.like');
    });
});
