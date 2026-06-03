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
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        // PostgreSQL cần raw SQL để chuyển kiểu cột đang tồn tại sang uuid.
        DB::statement('ALTER TABLE notifications ALTER COLUMN notifiable_id TYPE uuid USING notifiable_id::text::uuid');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('ALTER TABLE notifications ALTER COLUMN notifiable_id TYPE bigint USING notifiable_id::text::bigint');
    }
};
