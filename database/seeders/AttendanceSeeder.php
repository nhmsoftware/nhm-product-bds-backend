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

        $rows = [];

        // === 1. Employee@test.com: seed đủ 5 ngày T2→T6 tuần trước để demo điểm chuyên cần ===
        $primaryEmployee = $employees->firstWhere('email', 'employee@test.com');
        if ($primaryEmployee) {
            $weekDays = [
                ['day_offset' => -7, 'check_in' => '08:15:00', 'check_out' => '17:20:00', 'status' => AttendanceStatus::PRESENT, 'note' => 'Đi làm đúng giờ'],
                ['day_offset' => -6, 'check_in' => '09:05:00', 'check_out' => '18:10:00', 'status' => AttendanceStatus::LATE,    'note' => 'Đi làm trễ (Giờ quy định: 08:30)'],
                ['day_offset' => -5, 'check_in' => '08:25:00', 'check_out' => '17:30:00', 'status' => AttendanceStatus::PRESENT, 'note' => 'Đi làm đúng giờ'],
                ['day_offset' => -4, 'check_in' => '08:10:00', 'check_out' => '17:15:00', 'status' => AttendanceStatus::PRESENT, 'note' => 'Đi làm đúng giờ'],
                ['day_offset' => -3, 'check_in' => '08:20:00', 'check_out' => '17:30:00', 'status' => AttendanceStatus::LATE,    'note' => 'Đi làm trễ (Giờ quy định: 08:30)'],
            ];

            foreach ($weekDays as $day) {
                $workDate = now()->addDays($day['day_offset'])->toDateString();
                $rows[] = $this->buildRow($primaryEmployee->id, $workDate, $day);
            }
        }

        // === 2. Các employee khác: scenarios demo nhiều trạng thái ===
        $demoScenarios = [
            ['days_ago' => 5, 'check_in' => '08:20:00', 'check_out' => '17:30:00', 'status' => AttendanceStatus::PRESENT, 'note' => 'Đi làm đúng giờ'],
            ['days_ago' => 4, 'check_in' => '09:05:00', 'check_out' => '17:45:00', 'status' => AttendanceStatus::LATE,    'note' => 'Đi làm trễ (Giờ quy định: 08:30)'],
            ['days_ago' => 3, 'check_in' => '08:15:00', 'check_out' => '13:00:00', 'status' => AttendanceStatus::HALF_DAY, 'note' => 'Đi làm đúng giờ'],
            ['days_ago' => 2, 'check_in' => '08:25:00', 'check_out' => null,       'status' => AttendanceStatus::WORKING, 'note' => 'Đi làm đúng giờ'],
            ['days_ago' => 1, 'check_in' => '08:10:00', 'check_out' => '17:15:00', 'status' => AttendanceStatus::PRESENT, 'note' => 'Đi làm đúng giờ'],
            ['days_ago' => 1, 'check_in' => '09:15:00', 'check_out' => '18:00:00', 'status' => AttendanceStatus::LATE,    'note' => 'Đi làm trễ (Giờ quy định: 08:30)'],
            ['days_ago' => 6, 'check_in' => '08:30:00', 'check_out' => '12:30:00', 'status' => AttendanceStatus::HALF_DAY, 'note' => 'Đi làm đúng giờ'],
            ['days_ago' => 7, 'check_in' => '08:20:00', 'check_out' => null,       'status' => AttendanceStatus::HALF_DAY, 'note' => 'Đi làm đúng giờ | Thiếu check-out. Hệ thống tự động tính công mặc định: 0.5 công.'],
        ];

        $scenarioIndex = 0;
        foreach ($employees as $employee) {
            if ($employee->email === 'employee@test.com') {
                continue;
            }
            $scenario = $demoScenarios[$scenarioIndex % count($demoScenarios)];
            $workDate = now()->subDays($scenario['days_ago'])->toDateString();
            $rows[] = $this->buildRow($employee->id, $workDate, $scenario);
            $scenarioIndex++;
        }

        // Dùng updateOrInsert để tránh trùng unique constraint (user_id, work_date)
        // vì RevenueReportSeeder đã seed attendance cho cùng ngày
        foreach ($rows as $row) {
            $workDate = $row['work_date'];
            $userId = $row['user_id'];
            unset($row['work_date'], $row['user_id']);

            DB::table('attendances')->updateOrInsert(
                ['user_id' => $userId, 'work_date' => $workDate],
                $row
            );
        }
    }

    private function buildRow(string $userId, string $workDate, array $scenario): array
    {
        $checkOutTime = $scenario['check_out'];

        return [
            'id'                    => \Illuminate\Support\Str::uuid()->toString(),
            'user_id'               => $userId,
            'work_date'             => $workDate,
            'check_in_at'           => $workDate . ' ' . $scenario['check_in'],
            'check_in_lat'          => '21.0403323',
            'check_in_lng'          => '105.7734423',
            'check_in_method'       => 'gps',
            'check_in_wifi_ssid'    => null,
            'check_in_device_name'  => 'Demo Device',
            'check_out_at'          => $checkOutTime !== null ? $workDate . ' ' . $checkOutTime : null,
            'check_out_lat'         => $checkOutTime !== null ? '21.0403323' : null,
            'check_out_lng'         => $checkOutTime !== null ? '105.7734423' : null,
            'check_out_method'      => $checkOutTime !== null ? 'gps' : null,
            'check_out_wifi_ssid'   => null,
            'check_out_device_name' => $checkOutTime !== null ? 'Demo Device' : null,
            'status'                => $scenario['status']->value,
            'note'                  => $scenario['note'],
            'created_at'            => now(),
            'updated_at'            => now(),
        ];
    }
}
