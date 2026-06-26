<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->uuid('approver_id')->nullable()->after('user_id');
            $table->foreign('approver_id', 'fk_leave_requests_approver_id')
                  ->references('id')->on('users')
                  ->onDelete('set null');
            $table->index('approver_id', 'idx_leave_requests_approver_id');
        });
    }

    public function down(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->dropForeign('fk_leave_requests_approver_id');
            $table->dropIndex('idx_leave_requests_approver_id');
            $table->dropColumn('approver_id');
        });
    }
};
