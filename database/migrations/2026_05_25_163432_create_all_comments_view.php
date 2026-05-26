<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $connection = DB::connection()->getDriverName();

        if ($connection === 'sqlite') {
            DB::statement("DROP VIEW IF EXISTS v_all_comments");
            DB::statement("
                CREATE VIEW v_all_comments AS
                SELECT 
                    lc.id,
                    'lot_internal' AS source_type,
                    lc.lot_id AS source_id,
                    lc.user_id,
                    lc.content,
                    CAST(a.project_id AS text) AS project_id,
                    CAST(NULL AS text) AS department,
                    CAST(l.area_id AS text) AS area_id,
                    lc.created_at,
                    lc.updated_at,
                    lc.deleted_at
                FROM lot_comments lc
                LEFT JOIN lots l ON lc.lot_id = l.id
                LEFT JOIN areas a ON l.area_id = a.id
                
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
            ");
        } else {
            DB::statement("
                CREATE OR REPLACE VIEW v_all_comments AS
                SELECT 
                    lc.id,
                    'lot_internal' AS source_type,
                    lc.lot_id AS source_id,
                    lc.user_id,
                    lc.content,
                    CAST(a.project_id AS text) AS project_id,
                    CAST(NULL AS text) AS department,
                    CAST(l.area_id AS text) AS area_id,
                    lc.created_at,
                    lc.updated_at,
                    lc.deleted_at
                FROM lot_comments lc
                LEFT JOIN lots l ON lc.lot_id = l.id
                LEFT JOIN areas a ON l.area_id = a.id
                
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
            ");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS v_all_comments");
    }
};
