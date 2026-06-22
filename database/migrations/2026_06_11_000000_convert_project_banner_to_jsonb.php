<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'sqlite') {
            DB::statement(<<<'SQL'
                ALTER TABLE projects
                ALTER COLUMN banner TYPE jsonb
                USING CASE
                    WHEN banner IS NULL OR trim(banner) = '' THEN '[]'::jsonb
                    WHEN left(trim(banner), 1) = '[' THEN banner::jsonb
                    ELSE jsonb_build_array(banner)
                END
            SQL);
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'sqlite') {
            DB::statement(<<<'SQL'
                ALTER TABLE projects
                ALTER COLUMN banner TYPE varchar(255)
                USING CASE
                    WHEN jsonb_typeof(banner) = 'array' THEN banner->>0
                    ELSE banner::text
                END
            SQL);
        }
    }
};
