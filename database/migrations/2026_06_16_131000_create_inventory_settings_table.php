<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('inventory_settings')) {
            Schema::create('inventory_settings', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('key')->unique();
                $table->json('value')->nullable();
                $table->uuid('updated_by')->nullable();
                $table->timestamps();
            });
        }

        DB::table('inventory_settings')->updateOrInsert(
            ['key' => 'lot_lock_approval_timeout'],
            [
                'id' => (string) \Illuminate\Support\Str::uuid(),
                'value' => json_encode(['amount' => 24, 'unit' => 'hours'], JSON_UNESCAPED_UNICODE),
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_settings');
    }
};
