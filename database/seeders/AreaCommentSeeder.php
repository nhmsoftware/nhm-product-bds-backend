<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Modules\Area\Models\Area;
use App\Modules\Auth\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Str;

class AreaCommentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $areas = Area::all();
        $users = User::limit(5)->get();

        if ($areas->isEmpty() || $users->isEmpty()) {
            $this->command->info('Không có Area hoặc User để tạo comment fake.');
            return;
        }

        $comments = [];

        foreach ($areas as $area) {
            // Create 3 random comments per area
            for ($i = 0; $i < 3; $i++) {
                $user = $users->random();
                $comments[] = [
                    'id' => Str::uuid()->toString(),
                    'area_id' => $area->id,
                    'user_id' => $user->id,
                    'content' => 'Đây là bình luận tự động số ' . ($i + 1) . ' cho phân khu này. Vị trí rất đẹp và tiềm năng.',
                    'created_at' => Carbon::now()->subMinutes(rand(1, 10000)),
                    'updated_at' => Carbon::now(),
                ];
            }
        }

        DB::table('area_comments')->insert($comments);
        
        $this->command->info('Fake Area Comments đã được tạo thành công.');
    }
}
