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
        // 1. Drop the view to free the table dependency
        DB::statement("DROP VIEW IF EXISTS v_all_comments");

        // 2. Add area_id to lot_comments
        Schema::table('lot_comments', function (Blueprint $table) {
            $table->uuid('area_id')->nullable()->after('lot_id');
        });

        // 3. Migrate data from lot_id to area_id
        DB::statement("UPDATE lot_comments SET area_id = (SELECT area_id FROM lots WHERE lots.id = lot_comments.lot_id)");

        // 4. Drop lot_id and its foreign key, index
        Schema::table('lot_comments', function (Blueprint $table) {
            if (DB::connection()->getDriverName() !== 'sqlite') {
                $table->dropForeign('fk_lot_comments_lot_id');
            }
            $table->dropIndex('idx_lot_comments_lot_id');
            $table->dropColumn('lot_id');
        });

        // 5. Rename table
        Schema::rename('lot_comments', 'area_comments');

        // 6. Add constraints for area_id
        Schema::table('area_comments', function (Blueprint $table) {
            $table->foreign('area_id', 'fk_area_comments_area_id')->references('id')->on('areas')->onDelete('cascade');
            $table->index('area_id', 'idx_area_comments_area_id');
        });

        // 7. Recreate view
        $connection = DB::connection()->getDriverName();
        $createViewQuery = "
            SELECT 
                ac.id,
                'area_internal' AS source_type,
                ac.area_id AS source_id,
                ac.user_id,
                ac.content,
                CAST(a.project_id AS text) AS project_id,
                CAST(NULL AS text) AS department,
                CAST(ac.area_id AS text) AS area_id,
                ac.created_at,
                ac.updated_at,
                ac.deleted_at
            FROM area_comments ac
            LEFT JOIN areas a ON ac.area_id = a.id
            
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
        
        if ($connection === 'sqlite') {
            DB::statement("CREATE VIEW v_all_comments AS " . $createViewQuery);
        } else {
            DB::statement("CREATE OR REPLACE VIEW v_all_comments AS " . $createViewQuery);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS v_all_comments");

        Schema::table('area_comments', function (Blueprint $table) {
            if (DB::connection()->getDriverName() !== 'sqlite') {
                $table->dropForeign('fk_area_comments_area_id');
            }
            $table->dropIndex('idx_area_comments_area_id');
            $table->uuid('lot_id')->nullable()->after('area_id');
        });

        Schema::rename('area_comments', 'lot_comments');

        Schema::table('lot_comments', function (Blueprint $table) {
            $table->dropColumn('area_id');
            $table->foreign('lot_id', 'fk_lot_comments_lot_id')->references('id')->on('lots')->onDelete('cascade');
            $table->index('lot_id', 'idx_lot_comments_lot_id');
        });

        $connection = DB::connection()->getDriverName();
        $createViewQuery = "
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
        ";
        if ($connection === 'sqlite') {
            DB::statement("CREATE VIEW v_all_comments AS " . $createViewQuery);
        } else {
            DB::statement("CREATE OR REPLACE VIEW v_all_comments AS " . $createViewQuery);
        }
    }
};
