<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Modules\Consultation\Models\Enums\ConsultationStatus;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Thêm cột tạm thời
        Schema::table('consultation_messages', function (Blueprint $table) {
            $table->integer('status_int')->default(ConsultationStatus::PENDING->value)->after('status');
        });

        // 2. Data migration
        DB::table('consultation_messages')->chunkById(100, function ($rows) {
            foreach ($rows as $row) {
                $intValue = match (strtolower((string) $row->status)) {
                    'pending'   => ConsultationStatus::PENDING->value,
                    'processed' => ConsultationStatus::PROCESSED->value,
                    'cancelled' => ConsultationStatus::CANCELLED->value,
                    default     => ConsultationStatus::PENDING->value,
                };

                DB::table('consultation_messages')
                    ->where('id', $row->id)
                    ->update(['status_int' => $intValue]);
            }
        });

        // 3. Drop index cũ và cột cũ
        Schema::table('consultation_messages', function (Blueprint $table) {
            $table->dropIndex('idx_consultation_messages_status');
            $table->dropColumn('status');
        });

        // 4. Đổi tên cột mới và tạo lại index
        Schema::table('consultation_messages', function (Blueprint $table) {
            $table->renameColumn('status_int', 'status');
        });

        Schema::table('consultation_messages', function (Blueprint $table) {
            $table->index('status', 'idx_consultation_messages_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('consultation_messages', function (Blueprint $table) {
            $table->string('status_str', 50)->default('pending')->after('status');
        });

        DB::table('consultation_messages')->chunkById(100, function ($rows) {
            foreach ($rows as $row) {
                $strValue = match ((int) $row->status) {
                    ConsultationStatus::PENDING->value   => 'pending',
                    ConsultationStatus::PROCESSED->value => 'processed',
                    ConsultationStatus::CANCELLED->value => 'cancelled',
                    default                              => 'pending',
                };

                DB::table('consultation_messages')
                    ->where('id', $row->id)
                    ->update(['status_str' => $strValue]);
            }
        });

        Schema::table('consultation_messages', function (Blueprint $table) {
            $table->dropIndex('idx_consultation_messages_status');
            $table->dropColumn('status');
        });

        Schema::table('consultation_messages', function (Blueprint $table) {
            $table->renameColumn('status_str', 'status');
        });

        Schema::table('consultation_messages', function (Blueprint $table) {
            $table->index('status', 'idx_consultation_messages_status');
        });
    }
};
