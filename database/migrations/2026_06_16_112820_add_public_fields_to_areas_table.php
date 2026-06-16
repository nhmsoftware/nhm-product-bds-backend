<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('areas', function (Blueprint $table) {
            if (!Schema::hasColumn('areas', 'keywords')) {
                $table->json('keywords')->nullable()->after('name');
            }
            if (!Schema::hasColumn('areas', 'location')) {
                $table->string('location')->nullable()->after('keywords');
            }
            if (!Schema::hasColumn('areas', 'image')) {
                $table->string('image')->nullable()->after('location');
            }
            if (!Schema::hasColumn('areas', 'banner')) {
                $table->json('banner')->nullable()->after('image');
            }
            if (!Schema::hasColumn('areas', 'type')) {
                $table->string('type')->nullable()->after('status');
            }
            if (!Schema::hasColumn('areas', 'is_public')) {
                $table->boolean('is_public')->default(true)->after('type');
            }
            if (!Schema::hasColumn('areas', 'description')) {
                $table->text('description')->nullable()->after('is_public');
            }
            if (!Schema::hasColumn('areas', 'amenities')) {
                $table->json('amenities')->nullable()->after('description');
            }
            if (!Schema::hasColumn('areas', 'floor_plans')) {
                $table->json('floor_plans')->nullable()->after('amenities');
            }
            if (!Schema::hasColumn('areas', 'legal_info')) {
                $table->json('legal_info')->nullable()->after('floor_plans');
            }
            if (!Schema::hasColumn('areas', 'brochure')) {
                $table->string('brochure')->nullable()->after('legal_info');
            }
            if (!Schema::hasColumn('areas', 'contact_info')) {
                $table->json('contact_info')->nullable()->after('brochure');
            }
            if (!Schema::hasColumn('areas', 'google_maps_url')) {
                $table->string('google_maps_url')->nullable()->after('contact_info');
            }
            if (!Schema::hasColumn('areas', 'location_image')) {
                $table->string('location_image')->nullable()->after('google_maps_url');
            }
            if (!Schema::hasColumn('areas', 'planning_info')) {
                $table->json('planning_info')->nullable()->after('location_image');
            }
            if (!Schema::hasColumn('areas', 'branch')) {
                $table->string('branch')->nullable()->after('planning_info');
            }
            if (!Schema::hasColumn('areas', 'is_locked')) {
                $table->boolean('is_locked')->default(false)->after('is_featured');
            }
        });
    }

    public function down(): void
    {
        Schema::table('areas', function (Blueprint $table) {
            $columns = [
                'keywords',
                'location',
                'image',
                'banner',
                'type',
                'is_public',
                'description',
                'amenities',
                'floor_plans',
                'legal_info',
                'brochure',
                'contact_info',
                'google_maps_url',
                'location_image',
                'planning_info',
                'branch',
                'is_locked',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('areas', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
