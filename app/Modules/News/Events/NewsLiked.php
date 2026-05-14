<?php

namespace App\Modules\News\Events;

use App\Modules\News\Models\News;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class NewsLiked
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly News $news,
        public readonly string $userId
    ) {
    }
}
