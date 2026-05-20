<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->onDelete('cascade');
            $table->date('work_date');
            
            // Check-in details
            $table->timestamp('check_in_at')->nullable();
            $table->decimal('check_in_lat', 10, 7)->nullable();
            $table->decimal('check_in_lng', 10, 7)->nullable();
            $table->enum('check_in_method', ['gps', 'wifi', 'qr'])->nullable();
            $table->string('check_in_wifi_ssid')->nullable();
            $table->string('check_in_device_name')->nullable();

            // Check-out details (ready for future check-out feature)
            $table->timestamp('check_out_at')->nullable();
            $table->decimal('check_out_lat', 10, 7)->nullable();
            $table->decimal('check_out_lng', 10, 7)->nullable();
            $table->enum('check_out_method', ['gps', 'wifi', 'qr'])->nullable();
            $table->string('check_out_wifi_ssid')->nullable();
            $table->string('check_out_device_name')->nullable();

            $table->enum('status', ['present', 'late', 'absent', 'half_day'])->default('present');
            $table->text('note')->nullable();
            
            $table->timestamps();
            $table->softDeletes();

            // One check-in record per user per day
            $table->unique(['user_id', 'work_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
