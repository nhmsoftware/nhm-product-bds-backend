<?php

declare(strict_types=1);

namespace App\Modules\Area\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use OpenApi\Attributes as OA;

/**
 * Class LotComment
 *
 * @property string $id
 * @property string $lot_id
 * @property string $user_id
 * @property string $content
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Modules\Auth\Models\User|null $user
 * @property-read Lot|null $lot
 * @mixin \Eloquent
 */
#[OA\Schema(
    schema: 'LotComment',
    title: 'LotComment Model',
    description: 'Thông tin bình luận lô đất',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid', example: 'd3b07384-d113-4ec2-a5d6-c734b1234567'),
        new OA\Property(property: 'lot_id', type: 'string', format: 'uuid', example: 'd3b07384-d113-4ec2-a5d6-c734b2234567'),
        new OA\Property(property: 'user_id', type: 'string', format: 'uuid', example: 'd3b07384-d113-4ec2-a5d6-c734b3234567'),
        new OA\Property(property: 'content', type: 'string', example: 'Lô đất này có vị trí rất đẹp.'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
class LotComment extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $table = 'lot_comments';

    protected $fillable = [
        'lot_id',
        'user_id',
        'content',
    ];

    protected $casts = [
        'id' => 'string',
        'lot_id' => 'string',
        'user_id' => 'string',
    ];

    /**
     * Relationship to User who posted the comment
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
