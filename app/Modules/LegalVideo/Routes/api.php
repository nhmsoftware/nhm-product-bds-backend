<?php

use App\Modules\LegalVideo\Http\Controllers\LegalVideoController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| LegalVideo Module Routes
|--------------------------------------------------------------------------
| UC-027: View Legal Video Library
*/

Route::prefix('v1/legal-videos')->group(function () {
    Route::get('/', [LegalVideoController::class, 'index'])->name('legal-videos.index');
    Route::get('/{idOrSlug}', [LegalVideoController::class, 'show'])->name('legal-videos.show');
});
