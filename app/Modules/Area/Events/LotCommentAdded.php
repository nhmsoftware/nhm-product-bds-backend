<?php

declare(strict_types=1);

namespace App\Modules\Area\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Modules\Area\Models\LotComment;

final class LotCommentAdded implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly LotComment $comment,
        public readonly string $areaId
    ) {
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('lot.' . $this->comment->lot_id),
            new Channel('area.' . $this->areaId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'lot.comment.created';
    }

    public function broadcastWith(): array
    {
        return [
            'lot_id' => (string) $this->comment->lot_id,
            'area_id' => $this->areaId,
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
