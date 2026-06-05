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
 * @property string|null $sales_board_iframe
 * @property string|null $planning_check_url
 * @property array|null $sales_board_images
 * @property int $total_lots
 * @property int $remaining_lots
 * @property float|null $area_size
 * @property string|null $direction
 * @property int|null $price
 * @property int|null $unit_price
 * @property int|null $status
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
        new OA\Property(property: 'sales_board_iframe', type: 'string', nullable: true, example: 'https://quyhoach24h.vn?ref=C5WA63ND'),
        new OA\Property(property: 'planning_check_url', type: 'string', nullable: true, example: 'https://quyhoach24h.vn?ref=C5WA63ND'),
        new OA\Property(property: 'sales_board_images', type: 'array', items: new OA\Items(type: 'string'), nullable: true, example: ['https://example.com/images/sales_board_1.jpg']),
        new OA\Property(property: 'area_size', type: 'number', format: 'float', nullable: true, example: 120.5),
        new OA\Property(property: 'direction', type: 'string', nullable: true, example: 'Đông Nam'),
        new OA\Property(property: 'price', type: 'integer', nullable: true, example: 5000000000),
        new OA\Property(property: 'unit_price', type: 'integer', nullable: true, example: 45000000),
        new OA\Property(property: 'status', type: 'integer', nullable: true, example: 1),
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
        'sales_board_iframe',
        'planning_check_url',
        'sales_board_images',
        'total_lots',
        'remaining_lots',
        'area_size',
        'direction',
        'price',
        'unit_price',
        'status',
        'is_featured',
    ];

    protected $casts = [
        'id' => 'string',
        'total_lots' => 'integer',
        'remaining_lots' => 'integer',
        'area_size' => 'float',
        'price' => 'integer',
        'unit_price' => 'integer',
        'status' => 'integer',
        'is_featured' => 'boolean',
        'sales_board_images' => 'array',
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

    /**
     * Danh sách bình luận của khu đất.
     */
    public function comments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(AreaComment::class, 'area_id');
    }
}

