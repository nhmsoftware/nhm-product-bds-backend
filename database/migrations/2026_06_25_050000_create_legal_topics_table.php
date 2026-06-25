<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legal_topics', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('legal_videos', function (Blueprint $table) {
            $table->uuid('legal_topic_id')->nullable()->after('category');
            $table->foreign('legal_topic_id')
                ->references('id')->on('legal_topics')
                ->onDelete('set null');
        });

        // Predefined categories
        $categoriesMap = [
            'project_legal' => 'Pháp lý dự án',
            'contract' => 'Hợp đồng',
            'planning' => 'Quy hoạch',
            'transaction_process' => 'Quy trình giao dịch',
            'tax' => 'Thuế phí',
            'investment' => 'Đầu tư',
            'legal' => 'Pháp lý',
            'other' => 'Khác',
        ];

        // First seed default categories
        $topicIds = [];
        $index = 0;
        foreach ($categoriesMap as $key => $name) {
            $id = (string) Str::uuid();
            DB::table('legal_topics')->insert([
                'id' => $id,
                'name' => $name,
                'slug' => Str::slug($name),
                'is_active' => true,
                'sort' => ++$index,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $topicIds[$key] = $id;
        }

        // Migrate existing records
        $existingVideos = DB::table('legal_videos')->get();
        foreach ($existingVideos as $video) {
            $catKey = $video->category;
            $topicId = $topicIds[$catKey] ?? null;

            if (!$topicId && !empty($catKey)) {
                // If it is not in the predefined map, check if it's a custom string
                // and see if we already created a topic for it
                $topicName = $categoriesMap[$catKey] ?? $catKey;
                $existingTopic = DB::table('legal_topics')->where('name', $topicName)->first();

                if ($existingTopic) {
                    $topicId = $existingTopic->id;
                } else {
                    $topicId = (string) Str::uuid();
                    DB::table('legal_topics')->insert([
                        'id' => $topicId,
                        'name' => $topicName,
                        'slug' => Str::slug($topicName),
                        'is_active' => true,
                        'sort' => ++$index,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            if ($topicId) {
                DB::table('legal_videos')
                    ->where('id', $video->id)
                    ->update(['legal_topic_id' => $topicId]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('legal_videos', function (Blueprint $table) {
            $table->dropForeign(['legal_topic_id']);
            $table->dropColumn('legal_topic_id');
        });

        Schema::dropIfExists('legal_topics');
    }
};
