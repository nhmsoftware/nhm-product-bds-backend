<?php

declare(strict_types=1);

namespace App\Modules\LegalVideo\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use OpenApi\Attributes as OA;

/**
 * Class LegalTopic
 *
 * @property string $id
 * @property string $name
 * @property string $slug
 * @property bool $is_active
 * @property int $sort
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @mixin \Eloquent
 */
#[OA\Schema(
    schema: 'LegalTopic',
    title: 'LegalTopic Model',
    description: 'Chủ đề pháp lý bất động sản',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'name', type: 'string'),
        new OA\Property(property: 'slug', type: 'string'),
        new OA\Property(property: 'is_active', type: 'boolean'),
        new OA\Property(property: 'sort', type: 'integer'),
    ]
)]
class LegalTopic extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $table = 'legal_topics';

    protected $fillable = [
        'name',
        'slug',
        'is_active',
        'sort',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort' => 'integer',
    ];

    public function legalVideos()
    {
        return $this->hasMany(LegalVideo::class, 'legal_topic_id');
    }
}
