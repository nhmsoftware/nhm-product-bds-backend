<?php

use App\Modules\ActivityEvidence\Http\Controllers\EvidenceController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Activity Evidence Module Routes
|--------------------------------------------------------------------------
|
| UC-040: Upload Activity Evidence
|
*/

Route::middleware('auth:api')->prefix('v1/evidence')->group(function () {
    Route::post('/upload', [EvidenceController::class, 'upload'])->name('evidence.upload');
});
