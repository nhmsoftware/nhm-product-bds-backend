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
    Route::middleware('auth:api')->group(function () {
        Route::get('/internal', [NewsController::class, 'getInternalFeed'])->name('news.internal');
        Route::post('/internal', [NewsController::class, 'createInternal'])->name('news.internal.create');
        Route::get('/internal/{id}', [NewsController::class, 'getInternalDetail'])->name('news.internal.detail');
        Route::match(['post', 'put'], '/internal/{id}', [NewsController::class, 'updateInternal'])->name('news.internal.update');
        Route::post('/internal/{id}/comments', [NewsController::class, 'addComment'])->name('news.internal.comment');
        Route::post('/internal/{id}/like', [NewsController::class, 'likeInternal'])->name('news.internal.like');
        Route::post('/{id}/like', [NewsController::class, 'like'])->name('news.like');
    });

    Route::get('/', [NewsController::class, 'index'])->name('news.index');
    Route::get('/search', [NewsController::class, 'search'])->name('news.search');
    Route::get('/{idOrSlug}', [NewsController::class, 'show'])->name('news.show');
});
