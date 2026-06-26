<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('locked_at')->nullable()->after('is_active');
            $table->string('lock_reason')->nullable()->after('locked_at');
            $table->unsignedSmallInteger('lock_days')->nullable()->default(2)->after('lock_reason');
            $table->timestamp('lock_expires_at')->nullable()->after('lock_days');
            $table->foreignUuid('locked_by')->nullable()->after('lock_expires_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['locked_by']);
            $table->dropColumn(['locked_at', 'lock_reason', 'lock_days', 'lock_expires_at', 'locked_by']);
        });
    }
};
