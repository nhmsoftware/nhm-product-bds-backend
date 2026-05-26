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
        Schema::table('areas', function (Blueprint $table) {
            $table->uuid('project_id')->nullable()->after('id');
            $table->foreign('project_id', 'fk_areas_project_id')
                  ->references('id')->on('projects')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('areas', function (Blueprint $table) {
            $table->dropForeign('fk_areas_project_id');
            $table->dropColumn('project_id');
        });
    }
};
