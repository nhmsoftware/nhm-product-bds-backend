<?php

declare(strict_types=1);

namespace App\Modules\Notification\Events;

use App\Modules\Notification\Models\Notification;
use App\Modules\Auth\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

final class NotificationCreatedEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Notification $notification
    ) {
    }

    public function broadcastOn(): Channel
    {
        return new Channel('bds.communication.events');
    }

    public function broadcastAs(): string
    {
        return 'notification.created';
    }

    public function broadcastWith(): array
    {
        $data = $this->notification->data;
        $userId = (string) $this->notification->notifiable_id;

        return [
            'id' => (string) $this->notification->id,
            'title' => $data['title'] ?? 'Thông báo',
            'body' => $data['body'] ?? '',
            'user_id' => $userId,
            'notifiable_id' => $userId,
            'action_type' => $data['action_type'] ?? null,
            'action_id' => $data['action_id'] ?? null,
            'unread_count' => DB::table('notifications')
                ->where('notifiable_type', User::class)
                ->where('notifiable_id', $userId)
                ->whereNull('read_at')
                ->count(),
            'created_at' => $this->notification->created_at->toIso8601String(),
        ];
    }
}
