<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\Models;

use App\Modules\Auth\Models\User;
use App\Modules\Project\Models\Project;
use App\Modules\Area\Models\Lot;
use App\Modules\News\Models\News;
use Illuminate\Database\Eloquent\Model;
use OpenApi\Attributes as OA;

/**
 * Class SystemComment
 *
 * @property string $id
 * @property string $source_type
 * @property string $source_id
 * @property string $user_id
 * @property string $content
 * @property string|null $project_id
 * @property string|null $department
 * @property string|null $area_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read User|null $user
 * @mixin \Eloquent
 */
#[OA\Schema(
    schema: 'SystemComment',
    title: 'SystemComment Model',
    description: 'Thông tin bình luận hệ thống (gộp)',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'source_type', type: 'string', example: 'lot_internal'),
        new OA\Property(property: 'source_id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'user_id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'content', type: 'string'),
        new OA\Property(property: 'project_id', type: 'string', format: 'uuid', nullable: true),
        new OA\Property(property: 'department', type: 'string', nullable: true),
        new OA\Property(property: 'area_id', type: 'string', format: 'uuid', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
class SystemComment extends Model
{
    use SoftDeletes;

    protected $table = 'v_all_comments';

    // View is read-only
    public $incrementing = false;
    protected $keyType = 'string';

    protected $casts = [
        'id' => 'string',
        'source_id' => 'string',
        'user_id' => 'string',
        'project_id' => 'string',
        'area_id' => 'string',
    ];

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function project(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }
}
