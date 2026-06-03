<?php

declare(strict_types=1);

namespace App\Modules\Notification\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Modules\Notification\Models\Notification;

class NotificationCreatedEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Notification $notification;

    public function __construct(Notification $notification)
    {
        $this->notification = $notification;
    }

    public function broadcastOn(): Channel
    {
        // Theo yêu cầu của FE: BE publish đúng event/payload và đảm bảo chỉ gửi đúng user
        // Hoặc gửi với user_id/notifiable_id để FE lọc
        return new Channel('bds.communication.events');
    }

    public function broadcastAs(): string
    {
        return 'NotificationCreated';
    }

    public function broadcastWith(): array
    {
        $data = $this->notification->data;
        return [
            'id' => $this->notification->id,
            'title' => $data['title'] ?? 'Thông báo',
            'body' => $data['body'] ?? '',
            'user_id' => $this->notification->notifiable_id,
            'notifiable_id' => $this->notification->notifiable_id,
            'action_type' => $data['action_type'] ?? null,
            'action_id' => $data['action_id'] ?? null,
            'created_at' => $this->notification->created_at->toIso8601String(),
        ];
    }
}
