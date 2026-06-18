<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lot_lock_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('lot_lock_requests', 'customer_name')) {
                // Tên khách hàng nhân viên muốn giữ lô cho
                $table->string('customer_name')->nullable()->after('reason');
            }
        });
    }

    public function down(): void
    {
        Schema::table('lot_lock_requests', function (Blueprint $table) {
            if (Schema::hasColumn('lot_lock_requests', 'customer_name')) {
                $table->dropColumn('customer_name');
            }
        });
    }
};
