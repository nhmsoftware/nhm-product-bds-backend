<?php

declare(strict_types=1);

namespace App\Modules\News\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;/**
 * Class NewsLike
 *
 * @property string $id
 * @property string $user_id
 * @property string $news_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @mixin \Eloquent
 */
class NewsLike extends Model
{
    use HasUuids;

    protected $table = 'news_likes';

    protected $fillable = [
        'user_id',
        'news_id',
    ];
}
