<?php

declare(strict_types=1);

namespace App\Modules\Area\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Modules\Area\Models\AreaComment;

final class AreaCommentAdded implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly AreaComment $comment
    ) {
    }

    public function broadcastOn(): Channel
    {
        return new Channel('bds.communication.events');
    }

    public function broadcastAs(): string
    {
        return 'area.comment.created';
    }

    public function broadcastWith(): array
    {
        return [
            'area_id' => (string) $this->comment->area_id,
            'comment' => [
                'id' => (string) $this->comment->id,
                'user_id' => (string) $this->comment->user_id,
                'user_name' => $this->comment->user ? $this->comment->user->name : 'Nhân viên hệ thống',
                'content' => $this->comment->content,
                'created_at' => $this->comment->created_at ? $this->comment->created_at->toIso8601String() : null,
            ]
        ];
    }
}
