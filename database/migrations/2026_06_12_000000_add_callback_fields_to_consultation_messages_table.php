<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('consultation_messages', function (Blueprint $table) {
            $table->string('request_type', 30)->default('consultation');
            $table->string('preferred_callback_time', 255)->nullable();
            $table->index('request_type', 'idx_consultation_messages_request_type');
        });
    }

    public function down(): void
    {
        Schema::table('consultation_messages', function (Blueprint $table) {
            $table->dropIndex('idx_consultation_messages_request_type');
            $table->dropColumn(['request_type', 'preferred_callback_time']);
        });
    }
};
