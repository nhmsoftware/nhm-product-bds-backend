<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RewardPointHistoryDemoSeeder extends Seeder
{
    /**
     * Seed reward point history for the two employee demo accounts.
     */
    public function run(): void
    {
        $employees = [
            'employee@test.com' => [
                'title' => 'Nhân viên kinh doanh',
                'identity_card' => '001200400001',
                'histories' => [
                    [-30,  'Hoàn điểm do hủy lịch hẹn khách hàng', -1, -22],
                    [120,  'Check-in văn phòng đúng giờ 6 ngày liên tiếp', 1, -18],
                    [250,  'Đặt cọc thành công lô A-12', 2, -14],
                    [80,   'Hoàn thành khóa học bắt buộc', 1, -8],
                    [150,  'Cập nhật minh chứng gặp khách hợp lệ', 1, -3],
                    [300,  'Chốt giao dịch ưu tiên trong tháng', 3, -1],
                ],
            ],
            'employee2@test.com' => [
                'title' => 'Nhân viên kinh doanh mới',
                'identity_card' => '001200400002',
                'histories' => [
                    [60,   'Hoàn thành onboarding ngày đầu tiên', 1, -24],
                    [90,   'Check-in văn phòng đúng giờ', 1, -20],
                    [110,  'Thêm khách hàng tiềm năng mới', 1, -15],
                    [-20,  'Điều chỉnh điểm minh chứng chưa đạt', 0, -10],
                    [130,  'Tham gia đào tạo sản phẩm mới', 1, -5],
                    [180,  'Hỗ trợ cập nhật thông tin kho hàng', 2, -2],
                ],
            ],
        ];

        DB::transaction(function () use ($employees) {
            foreach ($employees as $email => $config) {
                $user = DB::table('users')->where('email', $email)->first();

                if (!$user) {
                    $this->command?->warn("User {$email} khong ton tai, bo qua.");
                    continue;
                }

                $reasons = array_map(
                    fn (array $history) => $history[1],
                    $config['histories']
                );
                $oldPrefix = '[' . 'DEMO' . '] ';
                $prefixedReasons = array_map(
                    fn (string $reason) => $oldPrefix . $reason,
                    $reasons
                );

                DB::table('reward_point_histories')
                    ->where('user_id', $user->id)
                    ->whereIn('reason', [...$reasons, ...$prefixedReasons])
                    ->delete();

                $totalPoints = 0;
                $totalStars = 0;

                foreach ($config['histories'] as [$points, $reason, $stars, $daysAgo]) {
                    $createdAt = Carbon::now()->addDays($daysAgo)->setTime(rand(8, 18), rand(0, 59), 0);
                    $totalPoints += $points;
                    $totalStars += $stars;

                    DB::table('reward_point_histories')->insert([
                        'id' => (string) Str::uuid(),
                        'user_id' => $user->id,
                        'points_changed' => $points,
                        'stars_changed' => $stars,
                        'reason' => $reason,
                        'related_id' => null,
                        'created_at' => $createdAt,
                        'updated_at' => $createdAt,
                    ]);
                }

                $existingProfile = DB::table('employee_profiles')->where('user_id', $user->id)->first();

                DB::table('employee_profiles')->updateOrInsert(
                    ['user_id' => $user->id],
                    [
                        'id' => $existingProfile->id ?? (string) Str::uuid(),
                        'employee_title' => $config['title'],
                        'identity_card' => $config['identity_card'],
                        'education' => 'Đại học',
                        'major' => 'Kinh doanh bất động sản',
                        'experience' => 'Tài khoản demo cho mobile app',
                        'reward_points' => $totalPoints,
                        'kpi_stars' => $totalStars,
                        'created_at' => $existingProfile->created_at ?? now(),
                        'updated_at' => now(),
                    ]
                );

                $this->command?->info("Seeded reward history for {$email}: {$totalPoints} points, {$totalStars} stars.");
            }
        });
    }
}
