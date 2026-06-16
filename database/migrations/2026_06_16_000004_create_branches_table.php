<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branches', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name')->unique();
            $table->string('code')->nullable();
            $table->string('area')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        $now = now();
        $branchNames = DB::table('projects')
            ->whereNotNull('branch')
            ->where('branch', '!=', '')
            ->distinct()
            ->orderBy('branch')
            ->pluck('branch')
            ->all();

        foreach ($branchNames as $index => $name) {
            DB::table('branches')->updateOrInsert(
                ['name' => $name],
                [
                    'id' => (string) Str::uuid(),
                    'code' => self::codeFromName((string) $name),
                    'area' => $name,
                    'is_active' => true,
                    'sort' => $index + 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('branches');
    }

    private static function codeFromName(string $name): string
    {
        return match ($name) {
            'Hà Nội' => 'HN',
            'Hồ Chí Minh' => 'HCM',
            'Đà Nẵng' => 'DN',
            default => strtoupper(Str::slug($name, '_')),
        };
    }
};
