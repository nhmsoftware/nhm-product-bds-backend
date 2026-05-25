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
        Schema::table('lots', function (Blueprint $table) {
            $table->string('image_url')->nullable()->after('status');
            $table->decimal('frontage', 8, 2)->nullable()->after('area_size');
            $table->string('legal')->nullable()->after('direction');
            $table->text('description')->nullable()->after('legal');
            $table->uuid('planning_id')->nullable()->after('description');

            $table->foreign('planning_id', 'fk_lots_planning_id')
                  ->references('id')->on('plannings')
                  ->onDelete('set null');
        });

        Schema::create('lot_comments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('lot_id');
            $table->uuid('user_id');
            $table->text('content');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('lot_id', 'fk_lot_comments_lot_id')
                  ->references('id')->on('lots')
                  ->onDelete('cascade');

            $table->foreign('user_id', 'fk_lot_comments_user_id')
                  ->references('id')->on('users')
                  ->onDelete('cascade');

            $table->index('lot_id', 'idx_lot_comments_lot_id');
            $table->index('user_id', 'idx_lot_comments_user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lot_comments');

        Schema::table('lots', function (Blueprint $table) {
            $table->dropForeign('fk_lots_planning_id');
            $table->dropColumn(['image_url', 'frontage', 'legal', 'description', 'planning_id']);
        });
    }
};
