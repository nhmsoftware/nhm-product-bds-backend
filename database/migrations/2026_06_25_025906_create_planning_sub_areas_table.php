<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('planning_sub_areas', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(\Illuminate\Support\Facades\DB::raw('gen_random_uuid()'));
            $table->string('name');                     // Tên phân khu: "Khu A", "Khu thương mại"...
            $table->string('color', 20)->default('#3B82F6'); // Mã màu HEX
            $table->string('description')->nullable();  // Mô tả ngắn (tuỳ chọn)
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('planning_sub_areas');
    }
};
