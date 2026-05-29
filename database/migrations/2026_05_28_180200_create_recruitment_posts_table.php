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
        Schema::create('recruitment_posts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            
            $table->string('title');
            $table->string('image')->nullable();
            $table->string('branch_name');
            $table->string('job_position');
            $table->string('department');
            $table->text('short_description')->nullable();
            $table->text('content')->nullable();
            $table->integer('status');

            $table->index('status', 'idx_recruitment_posts_status');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recruitment_posts');
    }
};
