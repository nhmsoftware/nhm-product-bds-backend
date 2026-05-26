<?php

declare(strict_types=1);

namespace App\Modules\Area\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use OpenApi\Attributes as OA;
use App\Modules\Area\Models\Enums\LotStatus;

/**
 * Class Lot
 *
 * @property string $id
 * @property string $area_id
 * @property string $code
 * @property LotStatus $status
 * @property float|null $area_size
 * @property string|null $direction
 * @property int|null $price
 * @property int|null $unit_price
 * @property int|null $coordinate_x
 * @property int|null $coordinate_y
 * @property int|null $width
 * @property int|null $height
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @mixin \Eloquent
 */
#[OA\Schema(
    schema: 'Lot',
    title: 'Lot Model',
    description: 'Thông tin chi tiết lô đất',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid', example: 'd3b07384-d113-4ec2-a5d6-c734b1234567'),
        new OA\Property(property: 'area_id', type: 'string', format: 'uuid', example: 'd3b07384-d113-4ec2-a5d6-c734b2234567'),
        new OA\Property(property: 'code', type: 'string', example: 'A-01'),
        new OA\Property(property: 'status', type: 'integer', example: \App\Modules\Area\Models\Enums\LotStatus::AVAILABLE->value),
        new OA\Property(property: 'area_size', type: 'number', format: 'float', nullable: true, example: 120.5),
        new OA\Property(property: 'direction', type: 'string', nullable: true, example: 'Đông Nam'),
        new OA\Property(property: 'price', type: 'integer', nullable: true, example: 5000000000),
        new OA\Property(property: 'unit_price', type: 'integer', nullable: true, example: 45000000),
        new OA\Property(property: 'coordinate_x', type: 'integer', nullable: true, example: 150),
        new OA\Property(property: 'coordinate_y', type: 'integer', nullable: true, example: 320),
        new OA\Property(property: 'width', type: 'integer', nullable: true, example: 60),
        new OA\Property(property: 'height', type: 'integer', nullable: true, example: 60),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
class Lot extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $table = 'lots';

    protected $fillable = [
        'area_id',
        'code',
        'status',
        'area_size',
        'direction',
        'price',
        'unit_price',
        'coordinate_x',
        'coordinate_y',
        'width',
        'height',
        'image_url',
        'frontage',
        'legal',
        'description',
        'planning_id',
        'is_locked',
    ];

    protected $casts = [
        'id' => 'string',
        'area_id' => 'string',
        'status' => LotStatus::class,
        'area_size' => 'float',
        'price' => 'integer',
        'unit_price' => 'integer',
        'coordinate_x' => 'integer',
        'coordinate_y' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
        'frontage' => 'float',
        'planning_id' => 'string',
        'is_locked' => 'boolean',
    ];

    /**
     * Relationship to Area
     */
    public function area(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Area::class, 'area_id');
    }

    /**
     * Relationship to Planning
     */
    public function planning(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Modules\Planning\Models\Planning::class, 'planning_id');
    }

    /**
     * Relationship to Lot comments
     */
    public function comments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(LotComment::class, 'lot_id');
    }

    /**
     * Relationship to Lot lock requests
     */
    public function lockRequests(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(LotLockRequest::class, 'lot_id');
    }

    /**
     * Relationship to Lot deposit requests
     */
    public function depositRequests(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(LotDepositRequest::class, 'lot_id');
    }

    public function setStatusAttribute($value)
    {
        if ($value === null) {
            $this->attributes['status'] = null;
            return;
        }
        $this->attributes['status'] = $value instanceof LotStatus ? $value->value : LotStatus::deserialize($value)->value;
    }

    public function toArray()
    {
        $array = parent::toArray();
        if (isset($array['status']) && $this->status instanceof LotStatus) {
            $array['status'] = $this->status->serialize();
        }
        return $array;
    }
}
