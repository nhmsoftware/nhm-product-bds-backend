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
        Schema::create('consultation_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            
            $table->string('hotline', 20);
            $table->string('email', 100)->nullable();
            $table->string('address', 255)->nullable();
            $table->boolean('is_callback_enabled')->default(true);
            $table->boolean('is_message_form_enabled')->default(true);
            $table->string('working_hours', 255)->nullable();
            $table->boolean('is_active')->default(true);
            
            $table->index('is_active', 'idx_consultation_settings_is_active');
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consultation_settings');
    }
};
