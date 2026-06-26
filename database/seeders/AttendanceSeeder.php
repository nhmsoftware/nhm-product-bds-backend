<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\Attendance\Models\Enums\AttendanceStatus;
use App\Modules\Attendance\Models\Attendance;
use App\Modules\Auth\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AttendanceSeeder extends Seeder
{
    public function run(): void
    {
        $employeeEmails = [
            'employee@test.com',
            'employee2@test.com',
            'employee.qn@test.com',
            'employee.ld@test.com',
            'employee.bt@test.com',
        ];

        $employees = User::whereIn('email', $employeeEmails)->get();

        if ($employees->isEmpty()) {
            return;
        }

        // Dùng ngày trong quá khứ để tránh trùng unique constraint (user_id, work_date)
        // với dữ liệu check-in thật của hôm nay
        $scenarios = [
            // 5 ngày trước, check-in đúng giờ + check-out đủ 6h → PRESENT 1.0 công
            [
                'check_in_time' => '08:20:00',
                'check_out_time' => '17:30:00',
                'status' => AttendanceStatus::PRESENT,
                'note' => 'Đi làm đúng giờ',
                'days_ago' => 5,
            ],
            // 4 ngày trước, check-in trễ + check-out đủ 6h → LATE 1.0 công
            [
                'check_in_time' => '09:05:00',
                'check_out_time' => '17:45:00',
                'status' => AttendanceStatus::LATE,
                'note' => 'Đi làm trễ (Giờ quy định: 08:30)',
                'days_ago' => 4,
            ],
            // 3 ngày trước, check-in + check-out dưới 6h → HALF_DAY 0.5 công
            [
                'check_in_time' => '08:15:00',
                'check_out_time' => '13:00:00',
                'status' => AttendanceStatus::HALF_DAY,
                'note' => 'Đi làm đúng giờ',
                'days_ago' => 3,
            ],
            // 2 ngày trước, check-in đúng giờ + chưa check-out → WORKING (đang làm việc)
            [
                'check_in_time' => '08:25:00',
                'check_out_time' => null,
                'status' => AttendanceStatus::WORKING,
                'note' => 'Đi làm đúng giờ',
                'days_ago' => 2,
            ],
            // hôm qua, check-in đúng giờ + check-out đủ 6h → PRESENT
            [
                'check_in_time' => '08:10:00',
                'check_out_time' => '17:15:00',
                'status' => AttendanceStatus::PRESENT,
                'note' => 'Đi làm đúng giờ',
                'days_ago' => 1,
            ],
            // hôm qua, check-in trễ + check-out đủ 6h → LATE
            [
                'check_in_time' => '09:15:00',
                'check_out_time' => '18:00:00',
                'status' => AttendanceStatus::LATE,
                'note' => 'Đi làm trễ (Giờ quy định: 08:30)',
                'days_ago' => 1,
            ],
            // 6 ngày trước, check-in + check-out dưới 6h → HALF_DAY
            [
                'check_in_time' => '08:30:00',
                'check_out_time' => '12:30:00',
                'status' => AttendanceStatus::HALF_DAY,
                'note' => 'Đi làm đúng giờ',
                'days_ago' => 6,
            ],
            // 7 ngày trước, thiếu check-out → HALF_DAY (auto cuối ngày)
            [
                'check_in_time' => '08:20:00',
                'check_out_time' => null,
                'status' => AttendanceStatus::HALF_DAY,
                'note' => 'Đi làm đúng giờ | Thiếu check-out. Hệ thống tự động tính công mặc định: 0.5 công.',
                'days_ago' => 7,
            ],
        ];

        $rows = [];
        $index = 0;

        foreach ($employees as $employee) {
            $scenario = $scenarios[$index % count($scenarios)];
            $workDate = now()->subDays($scenario['days_ago'])->toDateString();
            $checkInAt = $workDate . ' ' . $scenario['check_in_time'];
            $checkOutAt = $scenario['check_out_time'] !== null
                ? $workDate . ' ' . $scenario['check_out_time']
                : null;

            $rows[] = [
                'id' => \Illuminate\Support\Str::uuid()->toString(),
                'user_id' => $employee->id,
                'work_date' => $workDate,
                'check_in_at' => $checkInAt,
                'check_in_lat' => '21.0403323',
                'check_in_lng' => '105.7734423',
                'check_in_method' => 'gps',
                'check_in_wifi_ssid' => null,
                'check_in_device_name' => 'Demo Device',
                'check_out_at' => $checkOutAt,
                'check_out_lat' => $scenario['check_out_time'] !== null ? '21.0403323' : null,
                'check_out_lng' => $scenario['check_out_time'] !== null ? '105.7734423' : null,
                'check_out_method' => $scenario['check_out_time'] !== null ? 'gps' : null,
                'check_out_wifi_ssid' => null,
                'check_out_device_name' => $scenario['check_out_time'] !== null ? 'Demo Device' : null,
                'status' => $scenario['status']->value,
                'note' => $scenario['note'],
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $index++;
        }

        // Xóa data demo cũ trước khi insert để tránh trùng unique constraint
        $employeeIds = $employees->pluck('id')->toArray();
        DB::table('attendances')
            ->whereIn('user_id', $employeeIds)
            ->where('note', 'like', '%Đi làm%')
            ->delete();

        DB::table('attendances')->insert($rows);
    }
}
