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
        Schema::create('consultation_messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            
            $table->string('full_name', 255);
            $table->string('phone', 20);
            $table->string('email', 100)->nullable();
            
            $table->uuid('project_id')->nullable();
            $table->foreign('project_id', 'fk_consultation_messages_project_id')
                  ->references('id')->on('projects')
                  ->onDelete('set null');
                  
            $table->string('project_name', 255)->nullable();
            $table->text('content')->nullable();
            $table->string('status', 50)->default('pending');
            
            $table->index('status', 'idx_consultation_messages_status');
            $table->index('phone', 'idx_consultation_messages_phone');
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consultation_messages');
    }
};
