<?php

declare(strict_types=1);

namespace App\Modules\LegalVideo\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use OpenApi\Attributes as OA;

/**
 * Class LegalVideo
 *
 * @property string $id
 * @property string $title
 * @property string $slug
 * @property string $short_description
 * @property string $description
 * @property string $video_url
 * @property string $thumbnail_url
 * @property int $duration_seconds
 * @property string $category
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $published_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @mixin \Eloquent
 */
#[OA\Schema(
    schema: 'LegalVideo',
    title: 'LegalVideo Model',
    description: 'Thông tin video kiến thức và pháp lý bất động sản',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'title', type: 'string'),
        new OA\Property(property: 'slug', type: 'string'),
        new OA\Property(property: 'short_description', type: 'string', nullable: true),
        new OA\Property(property: 'description', type: 'string', nullable: true),
        new OA\Property(property: 'video_url', type: 'string'),
        new OA\Property(property: 'thumbnail_url', type: 'string', nullable: true),
        new OA\Property(property: 'duration_seconds', type: 'integer', nullable: true),
        new OA\Property(property: 'category', type: 'string'),
        new OA\Property(property: 'is_active', type: 'boolean'),
        new OA\Property(property: 'published_at', type: 'string', format: 'date-time', nullable: true),
    ]
)]
class LegalVideo extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $table = 'legal_videos';

    protected $fillable = [
        'title',
        'slug',
        'short_description',
        'description',
        'video_url',
        'thumbnail_url',
        'duration_seconds',
        'category',
        'is_active',
        'published_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'duration_seconds' => 'integer',
        'published_at' => 'datetime',
    ];
}
