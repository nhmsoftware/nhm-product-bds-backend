<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Thêm cột branch_id vào areas và recruitment_posts
        Schema::table('areas', function (Blueprint $table): void {
            $table->foreignUuid('branch_id')
                ->nullable()
                ->after('planning_info')
                ->constrained('branches')
                ->nullOnDelete();
        });

        Schema::table('recruitment_posts', function (Blueprint $table): void {
            $table->foreignUuid('branch_id')
                ->nullable()
                ->after('title')
                ->constrained('branches')
                ->nullOnDelete();
        });

        // 2. Di chuyển dữ liệu từ cột name cũ sang cột ID mới
        $branches = DB::table('branches')->get(['id', 'name']);
        foreach ($branches as $branch) {
            DB::table('areas')
                ->where('branch', $branch->name)
                ->update(['branch_id' => $branch->id]);

            DB::table('recruitment_posts')
                ->where('branch_name', $branch->name)
                ->update(['branch_id' => $branch->id]);
        }

        // 3. Xóa cột branch cũ
        Schema::table('areas', function (Blueprint $table): void {
            $table->dropColumn('branch');
        });

        Schema::table('recruitment_posts', function (Blueprint $table): void {
            $table->dropColumn('branch_name');
        });
    }

    public function down(): void
    {
        // Phục hồi lại cột cũ (rollback)
        Schema::table('areas', function (Blueprint $table): void {
            $table->string('branch')->nullable()->after('planning_info');
        });

        Schema::table('recruitment_posts', function (Blueprint $table): void {
            $table->string('branch_name')->nullable()->after('title');
        });

        $branches = DB::table('branches')->get(['id', 'name']);
        foreach ($branches as $branch) {
            DB::table('areas')
                ->where('branch_id', $branch->id)
                ->update(['branch' => $branch->name]);

            DB::table('recruitment_posts')
                ->where('branch_id', $branch->id)
                ->update(['branch_name' => $branch->name]);
        }

        Schema::table('areas', function (Blueprint $table): void {
            $table->dropForeign(['branch_id']);
            $table->dropColumn('branch_id');
        });

        Schema::table('recruitment_posts', function (Blueprint $table): void {
            $table->dropForeign(['branch_id']);
            $table->dropColumn('branch_id');
        });
    }
};
