<?php

declare(strict_types=1);

namespace App\Modules\Planning\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use OpenApi\Attributes as OA;

/**
 * Class Planning
 *
 * @property string $id
 * @property string $title
 * @property string $map_image
 * @property string $status
 * @property int $updated_year
 * @property string $description
 * @property string $city
 * @property string $district
 * @property string $sub_area
 * @property string $symbol
 * @property string $density
 * @property string $max_height
 * @property string $land_use_ratio
 * @property string $setback
 * @property string $land_type_notes
 * @property string $pdf_url
 * @property float $latitude
 * @property float $longitude
 * @property string $content
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @mixin \Eloquent
 */
#[OA\Schema(
    schema: 'Planning',
    title: 'Planning Model',
    description: 'Thông tin quy hoạch bất động sản',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid', example: 'uuid-string'),
        new OA\Property(property: 'title', type: 'string', example: 'Quy hoạch khu đô thị Thủ Thiêm'),
        new OA\Property(property: 'map_image', type: 'string', example: 'https://example.com/map.jpg'),
        new OA\Property(property: 'status', type: 'string', example: 'Đang triển khai'),
        new OA\Property(property: 'updated_year', type: 'integer', example: 2024),
        new OA\Property(property: 'description', type: 'string', example: 'Mô tả ngắn về quy hoạch khu vực này.'),
        new OA\Property(property: 'city', type: 'string', example: 'TP. Hồ Chí Minh'),
        new OA\Property(property: 'district', type: 'string', nullable: true, example: 'Quận 2'),
        new OA\Property(property: 'sub_area', type: 'string', nullable: true, example: 'Phân khu A1'),
        new OA\Property(property: 'symbol', type: 'string', nullable: true, example: 'OXOM-1'),
        new OA\Property(property: 'density', type: 'string', nullable: true, example: '40%'),
        new OA\Property(property: 'max_height', type: 'string', nullable: true, example: '25 tầng'),
        new OA\Property(property: 'land_use_ratio', type: 'string', nullable: true, example: '5.0'),
        new OA\Property(property: 'setback', type: 'string', nullable: true, example: '6m'),
        new OA\Property(property: 'land_type_notes', type: 'string', nullable: true, example: 'Chú giải về các loại đất...'),
        new OA\Property(property: 'pdf_url', type: 'string', nullable: true, example: 'https://example.com/plan.pdf'),
        new OA\Property(property: 'latitude', type: 'number', format: 'float', nullable: true, example: 10.7769),
        new OA\Property(property: 'longitude', type: 'number', format: 'float', nullable: true, example: 106.7009),
        new OA\Property(property: 'content', type: 'string', nullable: true, example: 'Nội dung chi tiết quy hoạch...'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
class Planning extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $table = 'plannings';

    protected $fillable = [
        'title',
        'map_image',
        'status',
        'updated_year',
        'description',
        'city',
        'district',
        'sub_area',
        'symbol',
        'density',
        'max_height',
        'land_use_ratio',
        'setback',
        'land_type_notes',
        'pdf_url',
        'latitude',
        'longitude',
        'content',
    ];

    protected $casts = [
        'id' => 'string',
        'updated_year' => 'integer',
        'latitude' => 'float',
        'longitude' => 'float',
    ];
}
