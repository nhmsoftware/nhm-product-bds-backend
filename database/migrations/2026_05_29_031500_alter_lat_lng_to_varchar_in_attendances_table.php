<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->string('check_in_lat')->nullable()->change();
            $table->string('check_in_lng')->nullable()->change();
            $table->string('check_out_lat')->nullable()->change();
            $table->string('check_out_lng')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Use raw SQL to handle PostgreSQL's explicit cast requirement from varchar to numeric
        // NULLIF is used to handle empty strings safely
        \Illuminate\Support\Facades\DB::statement("ALTER TABLE attendances ALTER COLUMN check_in_lat TYPE numeric(10, 7) USING NULLIF(check_in_lat, '')::numeric(10, 7)");
        \Illuminate\Support\Facades\DB::statement("ALTER TABLE attendances ALTER COLUMN check_in_lng TYPE numeric(10, 7) USING NULLIF(check_in_lng, '')::numeric(10, 7)");
        \Illuminate\Support\Facades\DB::statement("ALTER TABLE attendances ALTER COLUMN check_out_lat TYPE numeric(10, 7) USING NULLIF(check_out_lat, '')::numeric(10, 7)");
        \Illuminate\Support\Facades\DB::statement("ALTER TABLE attendances ALTER COLUMN check_out_lng TYPE numeric(10, 7) USING NULLIF(check_out_lng, '')::numeric(10, 7)");
    }
};
