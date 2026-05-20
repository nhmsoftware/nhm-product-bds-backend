<?php

namespace App\Modules\News\Events;

use App\Modules\News\Models\NewsComment;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class NewsCommentCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly NewsComment $comment
    ) {
    }
}
