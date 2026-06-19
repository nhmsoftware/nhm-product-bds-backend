<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

use App\Modules\Area\Models\LotLockRequest;
use App\Modules\Area\Models\Enums\LotLockRequestStatus;
use App\Modules\Area\Models\Enums\LotStatus;
use App\Modules\Attendance\Models\Attendance;
use App\Modules\Attendance\Models\Enums\AttendanceStatus;
use App\Modules\Area\Models\InventorySetting;
use Illuminate\Support\Facades\Schedule;

Schedule::call(function () {
    $expiredRequests = LotLockRequest::query()
        ->where('status', LotLockRequestStatus::APPROVED->value)
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

Schedule::call(function () {
    // Tìm tất cả các bản ghi chấm công từ ngày hôm trước trở về trước mà chưa check-out
    $todayStr = now()->toDateString();
    $missingCheckoutAttendances = Attendance::query()
        ->whereNull('check_out_at')
        ->where('work_date', '<', $todayStr)
        ->get();

    if ($missingCheckoutAttendances->isEmpty()) {
        return;
    }

    // Đọc cấu hình
    $noCheckoutSetting = InventorySetting::where('key', 'attendance_no_checkout_work_day')->first();
    $noCheckoutWorkDay = floatval(data_get($noCheckoutSetting?->value, 'work_day', 0.5));

    foreach ($missingCheckoutAttendances as $attendance) {
        $status = AttendanceStatus::HALF_DAY; // Mặc định 0.5 công
        $workDayStr = '0.5 công';
        if ($noCheckoutWorkDay == 1.0) {
            $status = $attendance->status; // Giữ nguyên PRESENT / LATE
            $workDayStr = '1.0 công';
        } elseif ($noCheckoutWorkDay == 0.0) {
            $status = AttendanceStatus::ABSENT;
            $workDayStr = '0.0 công';
        }

        // Tự động check-out lúc 23:59:59 của ngày làm việc đó
        $dummyCheckoutAt = \Carbon\Carbon::parse($attendance->work_date)->endOfDay();

        $attendance->update([
            'check_out_at' => $dummyCheckoutAt,
            'status' => $status,
            'note' => ($attendance->note ? $attendance->note . ' | ' : '') . 'Thiếu check-out. Hệ thống tự động tính công mặc định: ' . $workDayStr . '.',
        ]);
    }
})->dailyAt('00:05');
