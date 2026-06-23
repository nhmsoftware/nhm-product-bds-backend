<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recruitment_applications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->unsignedTinyInteger('applied_position'); // 1: EMPLOYEE, 2: MANAGER, 3: DIRECTOR
            $table->uuid('applied_branch_id');
            $table->string('status', 20)->default('pending'); // pending, approved, rejected
            $table->string('cv_url')->nullable();
            $table->text('introduction')->nullable();
            $table->string('education')->nullable();
            $table->string('experience')->nullable();
            $table->string('profile_url')->nullable();
            $table->uuid('approved_by')->nullable();
            $table->text('rejected_reason')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('applied_branch_id')->references('id')->on('branches')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recruitment_applications');
    }
};
