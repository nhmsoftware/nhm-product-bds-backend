<?php

declare(strict_types=1);

namespace App\Modules\Area\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use OpenApi\Attributes as OA;
use App\Modules\Area\Models\Enums\LotDepositRequestStatus;

/**
 * Class LotDepositRequest
 *
 * @property string $id
 * @property string $lot_id
 * @property string $user_id
 * @property LotDepositRequestStatus $status
 * @property string|null $reason
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Modules\Auth\Models\User|null $user
 * @property-read Lot|null $lot
 * @mixin \Eloquent
 */
#[OA\Schema(
    schema: 'LotDepositRequest',
    title: 'LotDepositRequest Model',
    description: 'Thông tin yêu cầu đặt cọc lô đất',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid', example: 'd3b07384-d113-4ec2-a5d6-c734b1234567'),
        new OA\Property(property: 'lot_id', type: 'string', format: 'uuid', example: 'd3b07384-d113-4ec2-a5d6-c734b2234567'),
        new OA\Property(property: 'user_id', type: 'string', format: 'uuid', example: 'd3b07384-d113-4ec2-a5d6-c734b3234567'),
        new OA\Property(property: 'status', type: 'integer', example: \App\Modules\Area\Models\Enums\LotDepositRequestStatus::PENDING->value),
        new OA\Property(property: 'reason', type: 'string', nullable: true, example: 'Khách hàng đặt cọc 50 triệu.'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
class LotDepositRequest extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $table = 'lot_deposit_requests';

    protected $fillable = [
        'lot_id',
        'user_id',
        'status',
        'reason',
        'reject_reason',
    ];

    protected $casts = [
        'id' => 'string',
        'lot_id' => 'string',
        'user_id' => 'string',
        'status' => LotDepositRequestStatus::class,
    ];

    /**
     * Relationship to User who requested the deposit
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

    public function setStatusAttribute($value)
    {
        if ($value === null) {
            $this->attributes['status'] = null;
            return;
        }
        $this->attributes['status'] = $value instanceof LotDepositRequestStatus ? $value->value : LotDepositRequestStatus::deserialize($value)->value;
    }

    public function toArray()
    {
        $array = parent::toArray();
        if (isset($array['status']) && $this->status instanceof LotDepositRequestStatus) {
            $array['status'] = $this->status->serialize();
        }
        return $array;
    }
}
