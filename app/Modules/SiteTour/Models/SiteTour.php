<?php

declare(strict_types=1);

namespace App\Modules\SiteTour\Models;

use App\Modules\Auth\Models\User;
use App\Modules\Project\Models\Project;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use OpenApi\Attributes as OA;

/**
 * Class SiteTour
 *
 * @property string $id
 * @property string $user_id
 * @property string $project_id
 * @property string $unit_code
 * @property string $customer_name
 * @property string $image_path
 * @property string $latitude
 * @property string $longitude
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read User|null $user
 * @property-read Project|null $project
 * @mixin \Eloquent
 */
#[OA\Schema(
    schema: 'SiteTour',
    title: 'SiteTour',
    required: ['id', 'user_id', 'project_id', 'unit_code', 'customer_name', 'image_path', 'latitude', 'longitude'],
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid', example: '550e8400-e29b-41d4-a716-446655440000'),
        new OA\Property(property: 'user_id', type: 'string', format: 'uuid', example: '11111111-1111-1111-1111-111111111111'),
        new OA\Property(property: 'project_id', type: 'string', format: 'uuid', example: '22222222-2222-2222-2222-222222222222'),
        new OA\Property(property: 'unit_code', type: 'string', example: 'Block A - Căn 12.05'),
        new OA\Property(property: 'customer_name', type: 'string', example: 'Nguyễn Văn Khách'),
        new OA\Property(property: 'image_path', type: 'string', example: '/storage/site_tours/verification.png'),
        new OA\Property(property: 'latitude', type: 'number', format: 'float', example: 10.7769),
        new OA\Property(property: 'longitude', type: 'number', format: 'float', example: 106.7009),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
class SiteTour extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $table = 'site_tours';

    protected $fillable = [
        'user_id',
        'project_id',
        'unit_code',
        'customer_name',
        'image_path',
        'latitude',
        'longitude',
    ];

    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

   // ─── Relationships ───────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }
}
