<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('customer_meetings', function (Blueprint $table) {
            $table->double('latitude')->nullable()->change();
            $table->double('longitude')->nullable()->change();
        });

        Schema::table('site_tours', function (Blueprint $table) {
            $table->double('latitude')->nullable()->change();
            $table->double('longitude')->nullable()->change();
        });

        Schema::table('plannings', function (Blueprint $table) {
            $table->double('latitude')->nullable()->change();
            $table->double('longitude')->nullable()->change();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('cccd', 20)->nullable()->after('phone');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('cccd');
        });
        
        Schema::table('customer_meetings', function (Blueprint $table) {
            $table->decimal('latitude', 10, 7)->change();
            $table->decimal('longitude', 10, 7)->change();
        });

        Schema::table('site_tours', function (Blueprint $table) {
            $table->decimal('latitude', 10, 7)->change();
            $table->decimal('longitude', 10, 7)->change();
        });

        Schema::table('plannings', function (Blueprint $table) {
            $table->decimal('latitude', 10, 7)->nullable()->change();
            $table->decimal('longitude', 10, 7)->nullable()->change();
        });
    }
};
