<?php

declare(strict_types=1);

namespace App\Modules\Auth\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OpenApi\Attributes as OA;

/**
 * Class RewardPointHistory
 *
 * @property string $id
 * @property string $user_id
 * @property int $points_changed
 * @property int $stars_changed
 * @property string|null $reason
 * @property string|null $related_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read User|null $user
 * @mixin \Eloquent
 */
#[OA\Schema(
    schema: 'RewardPointHistory',
    title: 'Reward Point History',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'user_id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'points_changed', type: 'integer'),
        new OA\Property(property: 'stars_changed', type: 'integer'),
        new OA\Property(property: 'reason', type: 'string', nullable: true),
        new OA\Property(property: 'related_id', type: 'string', format: 'uuid', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
class RewardPointHistory extends Model
{
    use HasUuids;

    protected $table = 'reward_point_histories';

    protected $fillable = [
        'user_id',
        'points_changed',
        'stars_changed',
        'reason',
        'related_id',
    ];

    /**
     * Tới người dùng
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
