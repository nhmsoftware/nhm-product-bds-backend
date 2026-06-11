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
 * Class News
 *
 * @property string $id
 * @property string $title
 * @property string $slug
 * @property string $summary
 * @property string $content
 * @property string $thumbnail
 * @property array|null $attachments
 * @property string $category
 * @property string $department
 * @property string $area
 * @property string $author_id
 * @property bool $is_published
 * @property bool $is_featured
 * @property int $sort
 * @property bool $is_liked
 * @property int $likes_count
 * @property \Illuminate\Support\Carbon|null $published_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read Author|null $author
 * @mixin \Eloquent
 */
#[OA\Schema(
    schema: 'News',
    title: 'News Model',
    description: 'Thông tin bài viết tin tức',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'title', type: 'string'),
        new OA\Property(property: 'slug', type: 'string'),
        new OA\Property(property: 'summary', type: 'string', nullable: true),
        new OA\Property(property: 'content', type: 'string'),
        new OA\Property(property: 'thumbnail', type: 'string', nullable: true),
        new OA\Property(property: 'attachments', type: 'array', nullable: true, items: new OA\Items(type: 'object')),
        new OA\Property(property: 'category', type: 'string'),
        new OA\Property(property: 'department', type: 'string', nullable: true),
        new OA\Property(property: 'area', type: 'string', nullable: true),
        new OA\Property(property: 'is_liked', type: 'boolean', example: false),
        new OA\Property(property: 'likes_count', type: 'integer'),
        new OA\Property(property: 'sort', type: 'integer'),
        new OA\Property(property: 'published_at', type: 'string', format: 'date-time'),
    ]
)]
class News extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $table = 'news';

    protected $fillable = [
        'title',
        'slug',
        'summary',
        'content',
        'thumbnail',
        'attachments',
        'category',
        'department',
        'area',
        'author_id',
        'is_published',
        'is_featured',
        'sort',
        'likes_count',
        'published_at',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'is_featured' => 'boolean',
        'sort' => 'integer',
        'likes_count' => 'integer',
        'attachments' => 'array',
        'published_at' => 'datetime',
    ];

   // ─── Relationships ───────────────────────────────────────────

    public function author(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Modules\Auth\Models\User::class, 'author_id');
    }

    public function comments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(NewsComment::class, 'news_id');
    }

    public function likes(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(NewsLike::class, 'news_id');
    }
}
