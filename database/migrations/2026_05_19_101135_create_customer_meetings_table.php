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
        Schema::create('customer_meetings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            
            // Foreign key to users (Employee)
            $table->uuid('user_id');
            $table->foreign('user_id', 'fk_customer_meetings_user_id')
                  ->references('id')->on('users')
                  ->onDelete('cascade');
            
            // Foreign key to projects
            $table->uuid('project_id');
            $table->foreign('project_id', 'fk_customer_meetings_project_id')
                  ->references('id')->on('projects')
                  ->onDelete('cascade');

            $table->string('customer_name');
            $table->string('customer_phone');
            $table->string('image_path');
            
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            
            $table->timestamps();
            $table->softDeletes();

            // Indexes for foreign keys
            $table->index('user_id', 'idx_customer_meetings_user_id');
            $table->index('project_id', 'idx_customer_meetings_project_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_meetings');
    }
};
