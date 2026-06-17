<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

use App\Modules\Area\Models\LotLockRequest;
use App\Modules\Area\Models\Enums\LotLockRequestStatus;
use App\Modules\Area\Models\Enums\LotStatus;
use Illuminate\Support\Facades\Schedule;

Schedule::call(function () {
    $expiredRequests = LotLockRequest::query()
        ->whereIn('status', [LotLockRequestStatus::PENDING->value, LotLockRequestStatus::APPROVED->value])
        ->whereNotNull('expires_at')
        ->where('expires_at', '<', now())
        ->get();

    foreach ($expiredRequests as $request) {
        $request->update(['status' => LotLockRequestStatus::EXPIRED->value]);
        $request->lot?->update([
            'status' => LotStatus::AVAILABLE->value,
            'is_locked' => false,
        ]);
    }
})->everyMinute();
