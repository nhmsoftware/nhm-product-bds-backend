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
        Schema::table('users', function (Blueprint $table) {
            $table->string('area')->nullable()->comment('Khu vực quản lý/khu vực làm việc');
        });

        Schema::table('news', function (Blueprint $table) {
            $table->string('department')->nullable()->comment('Phòng ban đăng tin/phòng ban được xem');
            $table->string('area')->nullable()->comment('Khu vực đăng tin/khu vực được xem');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('area');
        });

        Schema::table('news', function (Blueprint $table) {
            $table->dropColumn(['department', 'area']);
        });
    }
};
