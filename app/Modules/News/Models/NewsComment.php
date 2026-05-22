<?php

declare(strict_types=1);

namespace App\Modules\News\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use OpenApi\Attributes as OA;

/**
 * Class NewsComment
 *
 * @property string $id
 * @property string $news_id
 * @property string $user_id
 * @property string $content
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read User|null $user
 * @property-read News|null $news
 * @mixin \Eloquent
 */
#[OA\Schema(
    schema: 'NewsComment',
    title: 'NewsComment Model',
    description: 'Thông tin bình luận bài viết',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'news_id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'user_id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'content', type: 'string'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
class NewsComment extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $table = 'news_comments';

    protected $fillable = [
        'news_id',
        'user_id',
        'content',
    ];

   // ─── Relationships ───────────────────────────────────────────

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Modules\Auth\Models\User::class, 'user_id');
    }

    public function news(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(News::class, 'news_id');
    }
}
