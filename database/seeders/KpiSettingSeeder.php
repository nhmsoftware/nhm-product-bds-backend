<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class KpiSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            [
                'key' => 'lot_lock_approval_timeout',
                'value' => json_encode(['amount' => 24, 'unit' => 'hours'], JSON_UNESCAPED_UNICODE),
            ],
            [
                'key' => 'kpi_points_successful_transaction',
                'value' => json_encode(['points' => 10], JSON_UNESCAPED_UNICODE),
            ],
            [
                'key' => 'kpi_points_site_tour',
                'value' => json_encode(['points' => 1], JSON_UNESCAPED_UNICODE),
            ],
            [
                'key' => 'kpi_points_customer_meeting',
                'value' => json_encode(['points' => 0.5], JSON_UNESCAPED_UNICODE),
            ],
            [
                'key' => 'kpi_points_successful_referral',
                'value' => json_encode(['points' => 1], JSON_UNESCAPED_UNICODE),
            ],
            [
                'key' => 'kpi_points_work_day_rate',
                'value' => json_encode(['points' => 1, 'days' => 5], JSON_UNESCAPED_UNICODE),
            ],
            [
                'key' => 'kpi_points_absence_penalty',
                'value' => json_encode(['points' => 0.5], JSON_UNESCAPED_UNICODE),
            ],
            [
                'key' => 'attendance_no_checkout_work_day',
                'value' => json_encode(['work_day' => 0.5], JSON_UNESCAPED_UNICODE),
            ],
            [
                'key' => 'attendance_under_6_hours_work_day',
                'value' => json_encode(['work_day' => 0.5], JSON_UNESCAPED_UNICODE),
            ],
        ];

        foreach ($settings as $setting) {
            DB::table('inventory_settings')->updateOrInsert(
                ['key' => $setting['key']],
                [
                    'id' => (string) Str::uuid(),
                    'value' => $setting['value'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
