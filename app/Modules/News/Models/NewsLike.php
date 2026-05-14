<?php

namespace App\Modules\News\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class NewsLike extends Model
{
    use HasUuids;

    protected $table = 'news_likes';

    protected $fillable = [
        'user_id',
        'news_id',
    ];
}
