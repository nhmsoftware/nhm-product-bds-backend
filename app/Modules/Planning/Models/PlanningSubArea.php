<?php

namespace App\Modules\Planning\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class PlanningSubArea extends Model
{
    use HasUuids;

    protected $table = 'planning_sub_areas';

    protected $fillable = [
        'name',
        'color',
        'description',
        'is_active',
    ];

    protected $casts = [
        'id'        => 'string',
        'is_active' => 'boolean',
    ];

    /**
     * Lấy danh sách phân khu đang hoạt động → [name => "name (color)"] cho Select.
     */
    public static function activeOptions(): array
    {
        return static::where('is_active', true)
            ->orderBy('name')
            ->pluck('name', 'name')
            ->all();
    }
}
