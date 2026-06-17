<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 0. Drop view v_all_comments trước vì nó phụ thuộc vào news.area
        DB::statement('DROP VIEW IF EXISTS v_all_comments');

        // 1. Thêm branch_id vào bảng news
        Schema::table('news', function (Blueprint $table) {
            $table->uuid('branch_id')->nullable()->after('department');
            $table->foreign('branch_id', 'fk_news_branch_id')
                  ->references('id')
                  ->on('branches')
                  ->onDelete('set null');
        });

        // 2. Chuyển đổi dữ liệu news.area sang news.branch_id
        if (Schema::hasColumn('news', 'area')) {
            $newsItems = DB::table('news')->whereNotNull('area')->get();
            foreach ($newsItems as $news) {
                $branchId = DB::table('branches')->where('name', $news->area)->value('id');
                if ($branchId) {
                    DB::table('news')->where('id', $news->id)->update(['branch_id' => $branchId]);
                }
            }
        }

        // 3. Xóa cột area khỏi bảng news
        Schema::table('news', function (Blueprint $table) {
            $table->dropColumn('area');
        });

        // 4. Xóa cột area khỏi bảng users
        if (Schema::hasColumn('users', 'area')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('area');
            });
        }

        // 5. Tạo lại view v_all_comments sử dụng branch_id thay vì area
        $this->createCommentsView();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop view trước
        DB::statement('DROP VIEW IF EXISTS v_all_comments');

        // Khôi phục cột area trong bảng users
        Schema::table('users', function (Blueprint $table) {
            $table->string('area')->nullable()->comment('Khu vực quản lý/khu vực làm việc');
        });

        // Khôi phục cột area trong bảng news
        Schema::table('news', function (Blueprint $table) {
            $table->string('area')->nullable()->comment('Khu vực đăng tin/khu vực được xem');
        });

        // Di chuyển dữ liệu ngược lại từ news.branch_id sang news.area
        $newsItems = DB::table('news')->whereNotNull('branch_id')->get();
        foreach ($newsItems as $news) {
            $branchName = DB::table('branches')->where('id', $news->branch_id)->value('name');
            if ($branchName) {
                DB::table('news')->where('id', $news->id)->update(['area' => $branchName]);
            }
        }

        // Xóa foreign key và branch_id trong bảng news
        Schema::table('news', function (Blueprint $table) {
            $table->dropForeign('fk_news_branch_id');
            $table->dropColumn('branch_id');
        });

        // Tạo lại view v_all_comments phiên bản cũ (dùng n.area)
        $this->createOldCommentsView();
    }

    private function createCommentsView(): void
    {
        $query = "
            SELECT
                ac.id,
                'area_internal' AS source_type,
                ac.area_id AS source_id,
                ac.user_id,
                ac.content,
                CAST(ac.area_id AS text) AS project_id,
                CAST(NULL AS text) AS department,
                CAST(ac.area_id AS text) AS area_id,
                ac.created_at,
                ac.updated_at,
                ac.deleted_at
            FROM area_comments ac

            UNION ALL

            SELECT
                nc.id,
                CASE
                    WHEN n.department IS NOT NULL OR n.branch_id IS NOT NULL THEN 'news_internal'
                    ELSE 'news_public'
                END AS source_type,
                nc.news_id AS source_id,
                nc.user_id,
                nc.content,
                CAST(NULL AS text) AS project_id,
                CAST(n.department AS text) AS department,
                CAST(n.branch_id AS text) AS area_id,
                nc.created_at,
                nc.updated_at,
                nc.deleted_at
            FROM news_comments nc
            LEFT JOIN news n ON nc.news_id = n.id
        ";

        if (DB::connection()->getDriverName() === 'sqlite') {
            DB::statement('CREATE VIEW v_all_comments AS ' . $query);
            return;
        }

        DB::statement('CREATE OR REPLACE VIEW v_all_comments AS ' . $query);
    }

    private function createOldCommentsView(): void
    {
        $query = "
            SELECT
                ac.id,
                'area_internal' AS source_type,
                ac.area_id AS source_id,
                ac.user_id,
                ac.content,
                CAST(ac.area_id AS text) AS project_id,
                CAST(NULL AS text) AS department,
                CAST(ac.area_id AS text) AS area_id,
                ac.created_at,
                ac.updated_at,
                ac.deleted_at
            FROM area_comments ac

            UNION ALL

            SELECT
                nc.id,
                CASE
                    WHEN n.department IS NOT NULL OR n.area IS NOT NULL THEN 'news_internal'
                    ELSE 'news_public'
                END AS source_type,
                nc.news_id AS source_id,
                nc.user_id,
                nc.content,
                CAST(NULL AS text) AS project_id,
                CAST(n.department AS text) AS department,
                CAST(n.area AS text) AS area_id,
                nc.created_at,
                nc.updated_at,
                nc.deleted_at
            FROM news_comments nc
            LEFT JOIN news n ON nc.news_id = n.id
        ";

        if (DB::connection()->getDriverName() === 'sqlite') {
            DB::statement('CREATE VIEW v_all_comments AS ' . $query);
            return;
        }

        DB::statement('CREATE OR REPLACE VIEW v_all_comments AS ' . $query);
    }
};
