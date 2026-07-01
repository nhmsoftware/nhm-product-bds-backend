<?php

namespace Database\Seeders;

use App\Modules\Auth\Models\User;
use App\Modules\Auth\Models\Enums\UserRole;
use App\Modules\Area\Models\Area;
use App\Modules\Area\Models\Lot;
use App\Modules\Area\Models\LotDepositRequest;
use App\Modules\Area\Models\Enums\LotDepositRequestStatus;
use App\Modules\Area\Models\Enums\LotStatus;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RevenueReportSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $now = Carbon::now();
            $employees = User::whereHas('role', fn ($q) => $q->where('name', 'employee'))
                ->whereNotNull('department_id')
                ->whereNotNull('job_position_id')
                ->get();

            if ($employees->isEmpty()) {
                $this->command?->warn('Chưa có nhân viên nào trong hệ thống để seed dữ liệu báo cáo.');
                return;
            }

            // Lấy danh sách Lô đất
            $lots = Lot::all();
            if ($lots->isEmpty()) {
                $this->command?->warn('Chưa có lô đất nào trong hệ thống để seed giao dịch.');
                return;
            }

            // Lấy danh sách Khu đất/Dự án (Bảng projects đã gộp vào areas)
            $areas = Area::all();
            if ($areas->isEmpty()) {
                $this->command?->warn('Chưa có khu đất/dự án nào trong hệ thống.');
                return;
            }

            $this->command?->info('Bắt đầu seed dữ liệu giao dịch và hiệu suất cho báo cáo...');

            // 1. Seed Giao dịch đặt cọc (Lot Deposit Requests) -> Phục vụ Báo cáo doanh thu & Báo cáo nhân viên
            $lotIndex = 0;
            // Phân phối ngày từ đầu tháng hiện tại tới nay
            $startOfMonth = $now->copy()->startOfMonth();
            $daysCount = $now->day;

            foreach ($employees as $empIndex => $employee) {
                // Mỗi nhân viên sẽ có từ 2 đến 4 giao dịch thành công trong tháng
                $numTransactions = rand(2, 4);

                for ($i = 0; $i < $numTransactions; $i++) {
                    $lot = $lots[$lotIndex % $lots->count()];
                    $lotIndex++;

                    // Random ngày trong tháng hiện tại
                    $createdAt = $startOfMonth->copy()->addDays(rand(0, $daysCount - 1))->addHours(rand(8, 17));

                    // Cập nhật trạng thái Lô đất thành SOLD
                    $lot->update([
                        'status' => LotStatus::SOLD,
                        'is_locked' => false
                    ]);

                    // Tạo Yêu cầu đặt cọc Đã duyệt (status = 2)
                    LotDepositRequest::create([
                        'id' => (string) Str::uuid(),
                        'lot_id' => $lot->id,
                        'user_id' => $employee->id,
                        'status' => LotDepositRequestStatus::APPROVED->value,
                        'reason' => 'Khách hàng đặt cọc lô ' . $lot->code,
                        'created_at' => $createdAt,
                        'updated_at' => $createdAt,
                    ]);
                }

                // Seed giao dịch lịch sử (17 tháng trước đến nay) để vẽ biểu đồ doanh thu theo các tháng, quý, năm
                for ($m = 17; $m >= 1; $m--) {
                    $targetMonth = $now->copy()->subMonths($m);
                    $daysInMonth = $targetMonth->daysInMonth;

                    // Mỗi nhân viên có 0 đến 2 giao dịch mỗi tháng trong quá khứ
                    $numHistTransactions = rand(0, 2);

                    for ($i = 0; $i < $numHistTransactions; $i++) {
                        $lot = $lots[$lotIndex % $lots->count()];
                        $lotIndex++;

                        $createdAt = $targetMonth->copy()->addDays(rand(0, $daysInMonth - 1))->addHours(rand(8, 17));

                        // Tạo Yêu cầu đặt cọc Đã duyệt (status = 2) trong quá khứ (không cần cập nhật status Lô hiện tại)
                        LotDepositRequest::create([
                            'id' => (string) Str::uuid(),
                            'lot_id' => $lot->id,
                            'user_id' => $employee->id,
                            'status' => LotDepositRequestStatus::APPROVED->value,
                            'reason' => 'Khách hàng đặt cọc lô ' . $lot->code,
                            'created_at' => $createdAt,
                            'updated_at' => $createdAt,
                        ]);
                    }
                }

                // 2. Seed Lượt dẫn khách tham quan (Site Tours) -> Phục vụ Báo cáo nhân viên
                $numSiteTours = rand(5, 12);
                for ($s = 0; $s < $numSiteTours; $s++) {
                    $area = $areas->random();
                    $tourDate = $startOfMonth->copy()->addDays(rand(0, $daysCount - 1))->addHours(rand(8, 17));
                    DB::table('site_tours')->insert([
                        'id' => (string) Str::uuid(),
                        'user_id' => $employee->id,
                        'project_id' => $area->id,
                        'unit_code' => 'ECO-' . rand(100, 999),
                        'customer_name' => 'Khách tham quan ' . Str::random(5),
                        'image_path' => 'https://via.placeholder.com/640x480.png?text=Site+Tour',
                        'latitude' => 21.028511 + (rand(-1000, 1000) / 100000),
                        'longitude' => 105.804817 + (rand(-1000, 1000) / 100000),
                        'created_at' => $tourDate,
                        'updated_at' => $tourDate,
                    ]);
                }

                // 3. Seed Lượt gặp khách hàng (Customer Meetings) -> Phục vụ Báo cáo nhân viên
                $numMeetings = rand(4, 10);
                for ($m = 0; $m < $numMeetings; $m++) {
                    $area = $areas->random();
                    $meetingDate = $startOfMonth->copy()->addDays(rand(0, $daysCount - 1))->addHours(rand(8, 17));
                    DB::table('customer_meetings')->insert([
                        'id' => (string) Str::uuid(),
                        'user_id' => $employee->id,
                        'project_id' => $area->id,
                        'customer_name' => 'Khách gặp mặt ' . Str::random(5),
                        'customer_phone' => '09' . rand(10000000, 99999999),
                        'image_path' => 'https://via.placeholder.com/640x480.png?text=Meeting',
                        'latitude' => 21.028511 + (rand(-1000, 1000) / 100000),
                        'longitude' => 105.804817 + (rand(-1000, 1000) / 100000),
                        'created_at' => $meetingDate,
                        'updated_at' => $meetingDate,
                    ]);
                }

                // 4. Seed Lượt giới thiệu thành công (Referrals) -> Phục vụ Báo cáo nhân viên
                $numReferrals = rand(1, 5);
                for ($r = 0; $r < $numReferrals; $r++) {
                    $referralDate = $startOfMonth->copy()->addDays(rand(0, $daysCount - 1))->addHours(rand(8, 17));
                    DB::table('referral_histories')->insert([
                        'id' => (string) Str::uuid(),
                        'name' => 'Ứng viên giới thiệu ' . Str::random(5),
                        'phone' => '098' . rand(1000000, 9999999),
                        'referral_type' => 1, // 1: Tuyển dụng
                        'status' => 2, // 2: Đã đăng ký (APPROVED)
                        'scanned_at' => $referralDate->copy()->subHours(2),
                        'registered_at' => $referralDate,
                        'referrer_id' => $employee->id,
                        'referee_id' => null,
                        'created_at' => $referralDate,
                        'updated_at' => $referralDate,
                    ]);
                }

                // 5. Seed Chấm công (Attendances) -> Phục vụ Báo cáo nhân viên
                for ($day = 1; $day <= $daysCount; $day++) {
                    $workDate = $startOfMonth->copy()->addDays($day - 1);
                    if ($workDate->isWeekend()) {
                        continue;
                    }

                    // 85% đi làm đúng giờ/muộn, 15% vắng
                    $status = rand(1, 100) <= 85 ? rand(1, 2) : 3;

                    DB::table('attendances')->updateOrInsert(
                        [
                            'user_id' => $employee->id,
                            'work_date' => $workDate->format('Y-m-d'),
                        ],
                        [
                            'id' => (string) Str::uuid(),
                            'check_in_at' => $status !== 3 ? $workDate->copy()->hour(rand(7, 9))->minute(rand(0, 59))->second(rand(0, 59)) : null,
                            'check_in_lat' => $status !== 3 ? '21.028511' : null,
                            'check_in_lng' => $status !== 3 ? '105.804817' : null,
                            'check_in_method' => $status !== 3 ? 'gps' : null,
                            'status' => $status,
                            'created_at' => $workDate,
                            'updated_at' => $workDate,
                        ]
                    );
                }
            }

            $this->command?->info('Đã seed thành công dữ liệu báo cáo doanh thu và nhân sự.');
        });
    }
}
