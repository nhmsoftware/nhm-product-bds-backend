<?php

declare(strict_types=1);

namespace App\Modules\Area\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Modules\Area\Models\Enums\LotLockRequestStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use OpenApi\Attributes as OA;

/**
 * Class LotLockRequest
 *
 * @property string $id
 * @property string $lot_id
 * @property string $user_id
 * @property string|null $reason
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Modules\Auth\Models\User|null $user
 * @property-read Lot|null $lot
 * @mixin \Eloquent
 */
#[OA\Schema(
    schema: 'LotLockRequest',
    title: 'LotLockRequest Model',
    description: 'Thông tin yêu cầu lock lô đất',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid', example: 'd3b07384-d113-4ec2-a5d6-c734b1234567'),
        new OA\Property(property: 'lot_id', type: 'string', format: 'uuid', example: 'd3b07384-d113-4ec2-a5d6-c734b2234567'),
        new OA\Property(property: 'user_id', type: 'string', format: 'uuid', example: 'd3b07384-d113-4ec2-a5d6-c734b3234567'),
        new OA\Property(property: 'reason', type: 'string', nullable: true, example: 'Khách hàng hẹn cọc ngày mai.'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
class LotLockRequest extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $table = 'lot_lock_requests';

    protected $fillable = [
        'lot_id',
        'user_id',
        'reason',
        'status',
        'expires_at',
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejected_at',
        'reject_reason',
    ];

    protected $casts = [
        'id' => 'string',
        'lot_id' => 'string',
        'user_id' => 'string',
        'status' => LotLockRequestStatus::class,
        'expires_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    /**
     * Relationship to User who requested the lock
     */
    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Modules\Auth\Models\User::class, 'user_id');
    }

    /**
     * Relationship to Lot
     */
    public function lot(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Lot::class, 'lot_id');
    }
}
