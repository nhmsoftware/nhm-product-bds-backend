<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Modules\Project\Models\Enums\ProjectStatus;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Thêm cột tạm thời
        Schema::table('projects', function (Blueprint $table) {
            $table->integer('status_int')->default(ProjectStatus::OPENING->value)->after('status');
        });

        // 2. Data migration
        DB::table('projects')->chunkById(100, function ($rows) {
            foreach ($rows as $row) {
                $intValue = match (strtolower((string) $row->status)) {
                    'opening'     => ProjectStatus::OPENING->value,
                    'coming_soon' => ProjectStatus::COMING_SOON->value,
                    'sold_out'    => ProjectStatus::SOLD_OUT->value,
                    default       => ProjectStatus::OPENING->value,
                };

                DB::table('projects')
                    ->where('id', $row->id)
                    ->update(['status_int' => $intValue]);
            }
        });

        // 3. Xóa cột cũ và đổi tên cột mới
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('status');
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->renameColumn('status_int', 'status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->string('status_str')->default('opening')->after('status');
        });

        DB::table('projects')->chunkById(100, function ($rows) {
            foreach ($rows as $row) {
                $strValue = match ((int) $row->status) {
                    ProjectStatus::OPENING->value     => 'opening',
                    ProjectStatus::COMING_SOON->value => 'coming_soon',
                    ProjectStatus::SOLD_OUT->value    => 'sold_out',
                    default                           => 'opening',
                };

                DB::table('projects')
                    ->where('id', $row->id)
                    ->update(['status_str' => $strValue]);
            }
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('status');
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->renameColumn('status_str', 'status');
        });
    }
};
