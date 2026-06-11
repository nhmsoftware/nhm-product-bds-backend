<?php

namespace App\Modules\News\Events;

use App\Modules\News\Models\NewsComment;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class NewsCommentCreated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly NewsComment $comment
    ) {
    }

    public function broadcastOn(): Channel
    {
        return new Channel('bds.communication.events');
    }

    public function broadcastAs(): string
    {
        return 'news.comment.created';
    }

    public function broadcastWith(): array
    {
        return [
            'news_id' => (string) $this->comment->news_id,
            'comment' => [
                'id' => (string) $this->comment->id,
                'news_id' => (string) $this->comment->news_id,
                'user_id' => (string) $this->comment->user_id,
                'user_name' => $this->comment->user ? $this->comment->user->name : 'Người dùng hệ thống',
                'user_avatar' => $this->comment->user ? $this->comment->user->avatar : null,
                'content' => $this->comment->content,
                'created_at' => $this->comment->created_at ? $this->comment->created_at->toIso8601String() : null,
            ],
        ];
    }
}
