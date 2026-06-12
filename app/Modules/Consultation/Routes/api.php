<?php

use App\Modules\Consultation\Http\Controllers\ConsultationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Consultation Module Routes
|--------------------------------------------------------------------------
| UC-024: View Consultation Contact Configuration
*/

Route::prefix('v1/public/consultation')->group(function () {
    Route::get('/setting', [ConsultationController::class, 'show'])->name('public.consultation.setting');
    Route::post('/callback', [ConsultationController::class, 'callback'])->name('public.consultation.callback');
    Route::post('/submit', [ConsultationController::class, 'submit'])->name('public.consultation.submit');
});
