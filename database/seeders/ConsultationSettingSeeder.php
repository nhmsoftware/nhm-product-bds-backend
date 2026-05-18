<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ConsultationSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('consultation_settings')->insert([
            'id' => Str::uuid()->toString(),
            'hotline' => '1900633633',
            'email' => 'tuyen.nguyen@bdsapp.vn',
            'address' => 'Tầng 12, Tòa nhà Landmark 81, 720A Điện Biên Phủ, Phường 22, Quận Bình Thạnh, TP. Hồ Chí Minh',
            'is_callback_enabled' => true,
            'is_message_form_enabled' => true,
            'working_hours' => 'Thứ 2 - Thứ 7: 8:00 - 18:00',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
