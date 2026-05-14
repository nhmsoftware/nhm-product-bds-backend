<?php

namespace App\Modules\News\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use OpenApi\Attributes as OA;

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
        new OA\Property(property: 'category', type: 'string'),
        new OA\Property(property: 'likes_count', type: 'integer'),
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
        'category',
        'author_id',
        'is_published',
        'is_featured',
        'likes_count',
        'published_at',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'is_featured' => 'boolean',
        'likes_count' => 'integer',
        'published_at' => 'datetime',
    ];
}
