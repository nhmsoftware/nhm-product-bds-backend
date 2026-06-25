<?php

namespace Database\Seeders;

use App\Modules\Area\Models\AreaType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AreaTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            'Đất nền khu đô thị biển',
            'Khu nhà ở sinh thái',
            'Shophouse ven sông',
            'Biệt thự nghỉ dưỡng đồi thông',
            'Căn hộ dịch vụ',
            'Khu nghỉ dưỡng biển',
        ];

        foreach ($types as $name) {
            AreaType::firstOrCreate(
                ['name' => $name],
                ['id' => (string) Str::uuid()]
            );
        }
    }
}
