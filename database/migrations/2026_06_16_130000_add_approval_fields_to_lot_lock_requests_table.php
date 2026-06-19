<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lot_lock_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('lot_lock_requests', 'status')) {
                $table->unsignedTinyInteger('status')->default(2)->after('reason'); // 2 = APPROVED
            }
            if (!Schema::hasColumn('lot_lock_requests', 'expires_at')) {
                $table->timestamp('expires_at')->nullable()->after('status');
            }
            if (!Schema::hasColumn('lot_lock_requests', 'approved_by')) {
                $table->uuid('approved_by')->nullable()->after('expires_at');
            }
            if (!Schema::hasColumn('lot_lock_requests', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('approved_by');
            }
            if (!Schema::hasColumn('lot_lock_requests', 'rejected_by')) {
                $table->uuid('rejected_by')->nullable()->after('approved_at');
            }
            if (!Schema::hasColumn('lot_lock_requests', 'rejected_at')) {
                $table->timestamp('rejected_at')->nullable()->after('rejected_by');
            }
            if (!Schema::hasColumn('lot_lock_requests', 'reject_reason')) {
                $table->text('reject_reason')->nullable()->after('rejected_at');
            }
        });

        DB::table('lot_lock_requests')
            ->whereNull('status')
            ->update(['status' => 2]); // 2 = APPROVED
    }

    public function down(): void
    {
        Schema::table('lot_lock_requests', function (Blueprint $table) {
            foreach (['status', 'expires_at', 'approved_by', 'approved_at', 'rejected_by', 'rejected_at', 'reject_reason'] as $column) {
                if (Schema::hasColumn('lot_lock_requests', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
