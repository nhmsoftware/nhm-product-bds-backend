<?php

declare(strict_types=1);

namespace App\Modules\Area\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use OpenApi\Attributes as OA;

/**
 * Class Area
 *
 * @property string $id
 * @property string|null $project_id
 * @property string $name
 * @property string|null $sales_board_image
 * @property int $total_lots
 * @property int $remaining_lots
 * @property bool $is_featured
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @mixin \Eloquent
 */
#[OA\Schema(
    schema: 'Area',
    title: 'Area Model',
    description: 'Thông tin khu đất / bảng hàng',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid', example: 'd3b07384-d113-4ec2-a5d6-c734b1234567'),
        new OA\Property(property: 'project_id', type: 'string', format: 'uuid', nullable: true, example: 'd3b07384-d113-4ec2-a5d6-c734b1234568'),
        new OA\Property(property: 'name', type: 'string', example: 'Phân khu A - Golden Land'),
        new OA\Property(property: 'sales_board_image', type: 'string', nullable: true, example: 'https://example.com/images/sales_board.jpg'),
        new OA\Property(property: 'total_lots', type: 'integer', example: 100),
        new OA\Property(property: 'remaining_lots', type: 'integer', example: 45),
        new OA\Property(property: 'is_featured', type: 'boolean', example: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
class Area extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $table = 'areas';

    protected $fillable = [
        'project_id',
        'name',
        'sales_board_image',
        'total_lots',
        'remaining_lots',
        'is_featured',
    ];

    protected $casts = [
        'id' => 'string',
        'total_lots' => 'integer',
        'remaining_lots' => 'integer',
        'is_featured' => 'boolean',
    ];

    /**
     * Danh sách người dùng được cấp quyền truy cập khu đất này.
     */
    public function assignedUsers(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(
            \App\Modules\Auth\Models\User::class,
            'area_assignments',
            'area_id',
            'user_id'
        )->withTimestamps()->whereNull('area_assignments.deleted_at');
    }

    /**
     * Danh sách lô đất thuộc khu đất này.
     */
    public function lots(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Lot::class, 'area_id');
    }

    /**
     * Thuộc về dự án nào.
     */
    public function project(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Modules\Project\Models\Project::class, 'project_id');
    }
}

