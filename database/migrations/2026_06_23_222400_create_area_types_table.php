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
        Schema::create('area_types', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name')->unique();
            $table->timestamps();
            $table->softDeletes();
        });

        // Add area_type_id to areas
        Schema::table('areas', function (Blueprint $table) {
            $table->uuid('area_type_id')->nullable()->after('type');
            $table->foreign('area_type_id')
                ->references('id')->on('area_types')
                ->onDelete('set null');
        });

        // Migrate existing types
        $existingTypes = DB::table('areas')
            ->whereNotNull('type')
            ->where('type', '!=', '')
            ->pluck('type')
            ->unique();

        foreach ($existingTypes as $typeName) {
            $typeId = (string) Str::uuid();
            DB::table('area_types')->insert([
                'id' => $typeId,
                'name' => $typeName,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('areas')
                ->where('type', $typeName)
                ->update(['area_type_id' => $typeId]);
        }
    }

    public function down(): void
    {
        Schema::table('areas', function (Blueprint $table) {
            $table->dropForeign(['area_type_id']);
            $table->dropColumn('area_type_id');
        });

        Schema::dropIfExists('area_types');
    }
};
