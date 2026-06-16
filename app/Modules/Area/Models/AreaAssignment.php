<?php

declare(strict_types=1);

namespace App\Modules\Area\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use OpenApi\Attributes as OA;

/**
 * Class AreaAssignment
 *
 * @property string $id
 * @property string $area_id
 * @property string|null $user_id
 * @property string|null $assignable_id
 * @property string|null $assignable_type
 * @property array|null $permissions
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @mixin \Eloquent
 */
#[OA\Schema(
    schema: 'AreaAssignment',
    title: 'AreaAssignment Model',
    description: 'Bảng phân quyền/cấp quyền khu đất cho người dùng',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid', example: 'uuid-string'),
        new OA\Property(property: 'area_id', type: 'string', format: 'uuid', example: 'uuid-string'),
        new OA\Property(property: 'user_id', type: 'string', format: 'uuid', nullable: true, example: 'uuid-string'),
        new OA\Property(property: 'assignable_id', type: 'string', nullable: true, example: 'uuid-string'),
        new OA\Property(property: 'assignable_type', type: 'string', nullable: true, example: 'user'),
        new OA\Property(property: 'permissions', type: 'array', items: new OA\Items(type: 'string'), nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
class AreaAssignment extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $table = 'area_assignments';

    protected $fillable = [
        'area_id',
        'user_id',
        'assignable_id',
        'assignable_type',
        'permissions',
    ];

    protected $casts = [
        'id' => 'string',
        'area_id' => 'string',
        'user_id' => 'string',
        'assignable_id' => 'string',
        'permissions' => 'array',
    ];

    /**
     * Quan hệ tới khu đất.
     */
    public function area(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Area::class, 'area_id');
    }

    /**
     * Quan hệ tới người dùng.
     */
    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Modules\Auth\Models\User::class, 'user_id');
    }
}
