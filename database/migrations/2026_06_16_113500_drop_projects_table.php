<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('DROP VIEW IF EXISTS v_all_comments');

        $this->dropForeignIfExists('areas', 'fk_areas_project_id');
        $this->dropForeignIfExists('customer_meetings', 'fk_customer_meetings_project_id');
        $this->dropForeignIfExists('site_tours', 'fk_site_tours_project_id');
        $this->dropForeignIfExists('consultation_messages', 'fk_consultation_messages_project_id');

        if (Schema::hasTable('project_assignments')) {
            Schema::drop('project_assignments');
        }

        if (Schema::hasTable('areas') && Schema::hasColumn('areas', 'project_id')) {
            Schema::table('areas', function (Blueprint $table): void {
                $table->dropColumn('project_id');
            });
        }

        if (Schema::hasTable('projects')) {
            Schema::drop('projects');
        }

        $this->createCommentsView();
    }

    public function down(): void
    {
        // Bảng projects đã được gộp vào areas. Không tự khôi phục dữ liệu cũ.
    }

    private function dropForeignIfExists(string $table, string $constraint): void
    {
        if (!Schema::hasTable($table)) {
            return;
        }

        $driver = DB::connection()->getDriverName();
        if ($driver === 'pgsql') {
            DB::statement("ALTER TABLE {$table} DROP CONSTRAINT IF EXISTS {$constraint}");
            return;
        }

        if ($driver === 'mysql') {
            try {
                DB::statement("ALTER TABLE {$table} DROP FOREIGN KEY {$constraint}");
            } catch (Throwable) {
            }
        }
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
