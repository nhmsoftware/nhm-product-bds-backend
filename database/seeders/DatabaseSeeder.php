<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DatabaseSeeder extends Seeder
{
    /**
     * Chạy toàn bộ seed demo đang dùng cho hệ thống.
     *
     * Mỗi lần chạy sẽ dọn dữ liệu demo cũ trước để không còn lẫn bộ khu đất legacy.
     */
    public function run(): void
    {
        $this->cleanOldSeedData();

        $this->call([
            RolePermissionSeeder::class,
            DepartmentSeeder::class,
            AreaTypeSeeder::class,
            InventoryAreaSeeder::class,
            ConsultationSettingSeeder::class,
            KpiSettingSeeder::class,
            LegalVideoSeeder::class,
            LearningPathDemoSeeder::class,
            BdsCourseSeeder::class,
            NewsDemoSeeder::class,
            NewsCommentSeeder::class,
            AreaCommentSeeder::class,
            RecentActivitySeeder::class,
            RewardPointHistoryDemoSeeder::class,
            RevenueReportSeeder::class,
            AttendanceSeeder::class,
        ]);
    }

    private function cleanOldSeedData(): void
    {
        $this->command?->warn('Đang xóa dữ liệu seed/demo cũ trước khi seed lại...');

        Schema::disableForeignKeyConstraints();

        foreach ($this->tablesToClear() as $table) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            DB::table($table)->truncate();
        }

        if (Schema::hasTable('users')) {
            DB::table('users')
                ->whereIn('email', $this->demoUserEmails())
                ->delete();
        }

        Schema::enableForeignKeyConstraints();

        $this->command?->info('Đã xóa dữ liệu seed/demo cũ.');
    }

    /**
     * Thứ tự đã xếp từ bảng con sang bảng cha để chạy ổn cả khi DB không hỗ trợ tắt FK hoàn toàn.
     */
    private function tablesToClear(): array
    {
        return [
            'notifications',
            'reward_point_histories',
            'referral_commissions',
            'referral_histories',
            'referral_commission_configs',
            'news_likes',
            'news_comments',
            'area_comments',
            'lot_comments',
            'consultation_messages',
            'customer_meetings',
            'site_tours',
            'lot_deposit_requests',
            'lot_lock_requests',
            'quiz_attempts',
            'lesson_progress',
            'course_enrollments',
            'course_quizzes',
            'course_lessons',
            'courses',
            'legal_videos',
            'legal_topics',
            'news',
            'recruitment_posts',
            'consultation_settings',
            'inventory_settings',
            'area_assignments',
            'project_assignments',
            'lots',
            'planning_sub_areas',
            'plannings',
            'areas',
            'area_types',
            'projects',
            'branches',
            'attendances',
            'leave_requests',
            'department_transfer_requests',
            'employee_profiles',
            'departments',
        ];
    }

    private function demoUserEmails(): array
    {
        return [
            'employee@test.com',
            'employee2@test.com',
            'employee.qn@test.com',
            'employee.ld@test.com',
            'employee.bt@test.com',
            'candidate@test.com',
            'manager@test.com',
            'director@test.com',
            'ceo@test.com',
            'superadmin@test.com',
            'customer@test.com',
            'demo@example.com',
        ];
    }
}
