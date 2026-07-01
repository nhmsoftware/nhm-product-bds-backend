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
use App\Modules\Auth\Models\User;
use App\Modules\Auth\Models\RewardPointHistory;
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
        // Tính thời gian làm việc thực tế (check-in đến hết ngày)
        $checkInAt = \Carbon\Carbon::parse($attendance->check_in_at);
        $endOfDay = \Carbon\Carbon::parse($attendance->work_date)->endOfDay();
        $durationInSeconds = max(0, (int) abs($endOfDay->diffInSeconds($checkInAt)));
        $hours = intdiv($durationInSeconds, 3600);
        $minutes = intdiv($durationInSeconds % 3600, 60);
        $durationText = "{$hours} giờ {$minutes} phút";

        // Xác định trạng thái cuối cùng theo cấu hình
        $status = AttendanceStatus::HALF_DAY;
        $workDayStr = '0.5 công';

        if ($durationInSeconds >= 21600) {
            // Đủ 6 tiếng hoặc hơn → 1 công (giữ nguyên PRESENT/LATE từ check-in)
            $status = $attendance->status === AttendanceStatus::WORKING
                ? AttendanceStatus::PRESENT
                : $attendance->status;
            $workDayStr = '1.0 công';
        } elseif ($noCheckoutWorkDay == 1.0) {
            // Dưới 6h nhưng config cho phép tính 1 công
            $status = $attendance->status === AttendanceStatus::WORKING
                ? AttendanceStatus::PRESENT
                : $attendance->status;
            $workDayStr = '1.0 công';
        } elseif ($noCheckoutWorkDay == 0.0) {
            $status = AttendanceStatus::ABSENT;
            $workDayStr = '0.0 công';
        }

        // Tự động check-out lúc 23:59:59 của ngày làm việc đó
        $attendance->update([
            'check_out_at' => $endOfDay,
            'status' => $status,
            'note' => ($attendance->note ? $attendance->note . ' | ' : '') . 'Thiếu check-out. Thời gian thực tế: ' . $durationText . '. Hệ thống tự động tính công mặc định: ' . $workDayStr . '.',
        ]);
    }
})->dailyAt('00:05');

/*
 * Scheduler tính điểm chuyên cần hàng tuần.
 * Chạy cuối ngày T6 (23:55) — kiểm tuần T2→T6.
 *
 * Quy tắc:
 * - Ngày công = PRESENT hoặc LATE (>= 6h)
 * - Đủ N ngày công trong tuần → cộng X điểm
 * - Ngày cuối tuần (T7, CN) không tính
 * - Không cộng dồn sang tuần sau
 */
Schedule::call(function () {
    $setting = InventorySetting::where('key', 'kpi_points_work_day_rate')->first();
    if (!$setting) {
        return;
    }

    $requiredDays = (int) data_get($setting->value, 'days', 5);
    $pointsToAward = (int) data_get($setting->value, 'points', 1);

    if ($requiredDays <= 0 || $pointsToAward <= 0) {
        return;
    }

    // Lấy tuần hiện tại: T2 → CN (Carbon default: Monday start)
    $now = now();
    $weekStart = $now->copy()->startOfWeek();  // T2
    $weekEnd = $now->copy()->endOfWeek();       // CN

    // Lấy tất cả employees active
    $employeeRoleId = \Illuminate\Support\Facades\DB::table('roles')->where('name', 'employee')->value('id');
    $employees = User::where('role_id', $employeeRoleId)
        ->where('is_active', true)
        ->get();

    foreach ($employees as $employee) {
        // Đếm số ngày công (PRESENT hoặc LATE) trong tuần
        $workDays = Attendance::where('user_id', $employee->id)
            ->whereBetween('work_date', [$weekStart->toDateString(), $weekEnd->toDateString()])
            ->whereIn('status', [
                AttendanceStatus::PRESENT->value,
                AttendanceStatus::LATE->value,
            ])
            ->count();

        if ($workDays < $requiredDays) {
            continue;
        }

        // Kiểm tra đã cộng điểm tuần này chưa (tránh chạy lại)
        $alreadyAwarded = RewardPointHistory::where('user_id', $employee->id)
            ->where('reason', 'like', '%Chuyên cần tuan ' . $weekStart->toDateString() . '%')
            ->exists();

        if ($alreadyAwarded) {
            continue;
        }

        // Cộng điểm
        if ($employee->employeeProfile) {
            $employee->employeeProfile->reward_points += $pointsToAward;
            $employee->employeeProfile->save();
        }

        RewardPointHistory::create([
            'user_id' => $employee->id,
            'points_changed' => $pointsToAward,
            'reason' => "Chuyên cần tuan {$weekStart->toDateString()}~{$weekEnd->toDateString()}: {$workDays}/{$requiredDays} ngay cong → +{$pointsToAward} diem",
        ]);
    }
})->fridays()->at('23:55');
