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
        Schema::table('projects', function (Blueprint $table) {
            $table->string('banner')->nullable()->after('image');
            $table->jsonb('amenities')->nullable()->after('description');
            $table->jsonb('floor_plans')->nullable()->after('amenities');
            $table->jsonb('legal_info')->nullable()->after('floor_plans');
            $table->string('brochure')->nullable()->after('legal_info');
            $table->jsonb('contact_info')->nullable()->after('brochure');
            $table->string('google_maps_url')->nullable()->after('location');
            $table->jsonb('planning_info')->nullable()->after('google_maps_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn([
                'banner',
                'amenities',
                'floor_plans',
                'legal_info',
                'brochure',
                'contact_info',
                'google_maps_url',
                'planning_info',
            ]);
        });
    }
};
