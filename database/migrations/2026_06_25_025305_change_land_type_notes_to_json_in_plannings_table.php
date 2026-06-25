<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Chuyển đổi dữ liệu cũ (text) → JSON array [{label, color}]
        // trước khi đổi kiểu cột
        $rows = DB::table('plannings')
            ->whereNotNull('land_type_notes')
            ->where('land_type_notes', '!=', '')
            ->get(['id', 'land_type_notes']);

        $defaultColors = [
            '#EF4444', '#F97316', '#EAB308', '#22C55E',
            '#14B8A6', '#3B82F6', '#8B5CF6', '#EC4899',
            '#6B7280', '#78350F',
        ];

        foreach ($rows as $row) {
            // Nếu đã là JSON hợp lệ (array) thì bỏ qua
            $decoded = json_decode($row->land_type_notes, true);
            if (is_array($decoded)) {
                continue;
            }

            // Parse text cũ (tách theo \n, ; hoặc ,)
            $labels = array_values(array_filter(
                array_map('trim', preg_split('/[\n;,]+/', $row->land_type_notes)),
                fn ($s) => $s !== ''
            ));

            $newValue = array_map(function ($label, $index) use ($defaultColors) {
                return [
                    'label' => $label,
                    'color' => $defaultColors[$index % count($defaultColors)],
                ];
            }, $labels, array_keys($labels));

            DB::table('plannings')
                ->where('id', $row->id)
                ->update(['land_type_notes' => json_encode($newValue, JSON_UNESCAPED_UNICODE)]);
        }

        DB::statement('ALTER TABLE plannings ALTER COLUMN land_type_notes TYPE json USING land_type_notes::json');
    }

    public function down(): void
    {
        Schema::table('plannings', function (Blueprint $table) {
            $table->text('land_type_notes')->nullable()->change();
        });
    }
};
