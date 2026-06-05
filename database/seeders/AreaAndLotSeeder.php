<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Modules\Area\Models\Area;
use App\Modules\Area\Models\Lot;
use App\Modules\Project\Models\Project;
use Illuminate\Support\Str;
use App\Modules\Area\Models\Enums\LotStatus;

class AreaAndLotSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $project = Project::first();

        if (!$project) {
            $this->command->info('Không có Project nào. Vui lòng tạo Project trước.');
            return;
        }

        // Tạo 3 Khu đất (Areas) fake
        $areas = [];
        for ($i = 1; $i <= 3; $i++) {
            $area = Area::create([
                'id' => Str::uuid()->toString(),
                'project_id' => $project->id,
                'name' => 'Phân khu Fake ' . $i,
                'sales_board_image' => 'https://via.placeholder.com/800x600?text=Sales+Board+' . $i,
                'sales_board_iframe' => null,
                'planning_check_url' => 'https://quyhoach24h.vn',
                'total_lots' => 10,
                'remaining_lots' => 8,
                'area_size' => rand(100, 500) + 0.5,
                'direction' => 'Đông Nam',
                'price' => rand(2000, 5000) * 1000000,
                'unit_price' => rand(20, 50) * 1000000,
                'status' => 1,
                'is_featured' => true,
            ]);
            $areas[] = $area;
        }

        // Tạo Lots (Lô đất) cho từng Area
        foreach ($areas as $area) {
            for ($j = 1; $j <= 10; $j++) {
                $status = LotStatus::AVAILABLE;
                if ($j == 9) $status = LotStatus::RESERVED;
                if ($j == 10) $status = LotStatus::SOLD;

                Lot::create([
                    'id' => Str::uuid()->toString(),
                    'area_id' => $area->id,
                    'code' => 'L-' . $area->name . '-' . sprintf('%02d', $j),
                    'status' => $status,
                    'area_size' => rand(80, 150) + 0.5,
                    'direction' => 'Tây Bắc',
                    'price' => rand(1500, 3000) * 1000000,
                    'unit_price' => rand(15, 30) * 1000000,
                    'coordinate_x' => rand(10, 800),
                    'coordinate_y' => rand(10, 600),
                    'width' => rand(5, 10),
                    'height' => rand(15, 20),
                    'image_url' => 'https://via.placeholder.com/400x300?text=Lot+' . $j,
                    'frontage' => rand(4, 8) + 0.5,
                    'legal' => 'Sổ đỏ',
                    'description' => 'Mô tả lô đất fake ' . $j,
                    'is_locked' => false,
                ]);
            }
        }

        $this->command->info('Fake Areas và Lots đã được tạo thành công.');
    }
}
