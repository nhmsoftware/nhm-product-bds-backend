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
        Schema::create('plannings', function (Blueprint $blueprint) {
            $blueprint->uuid('id')->primary();
            $blueprint->string('title');
            $blueprint->string('map_image');
            $blueprint->string('status');
            $blueprint->integer('updated_year');
            $blueprint->text('description');
            $blueprint->string('city');
            $blueprint->longText('content')->nullable();
            $blueprint->softDeletes();
            $blueprint->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plannings');
    }
};
