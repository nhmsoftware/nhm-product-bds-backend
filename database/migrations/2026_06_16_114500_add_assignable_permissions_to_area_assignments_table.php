<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::connection()->getDriverName();
        if (Schema::hasColumn('area_assignments', 'user_id')) {
            if ($driver === 'pgsql') {
                DB::statement('ALTER TABLE area_assignments ALTER COLUMN user_id DROP NOT NULL');
            } elseif ($driver === 'mysql') {
                DB::statement('ALTER TABLE area_assignments MODIFY user_id CHAR(36) NULL');
            }
        }

        Schema::table('area_assignments', function (Blueprint $table): void {
            if (!Schema::hasColumn('area_assignments', 'assignable_id')) {
                $table->string('assignable_id')->nullable()->after('user_id');
            }
            if (!Schema::hasColumn('area_assignments', 'assignable_type')) {
                $table->string('assignable_type')->nullable()->after('assignable_id');
            }
            if (!Schema::hasColumn('area_assignments', 'permissions')) {
                $table->json('permissions')->nullable()->after('assignable_type');
            }
            $table->index(['assignable_id', 'assignable_type'], 'idx_area_assignments_assignable');
        });

        DB::table('area_assignments')
            ->whereNull('assignable_id')
            ->whereNotNull('user_id')
            ->update([
                'assignable_id' => DB::raw('user_id'),
                'assignable_type' => 'user',
                'permissions' => json_encode([
                    'view_project',
                    'view_area',
                    'view_lot',
                    'lock_lot',
                    'deposit_lot',
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ]);
    }

    public function down(): void
    {
        Schema::table('area_assignments', function (Blueprint $table): void {
            foreach (['permissions', 'assignable_type', 'assignable_id'] as $column) {
                if (Schema::hasColumn('area_assignments', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
