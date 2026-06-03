<?php

declare(strict_types=1);

namespace App\Modules\Notification\Observers;

use App\Broadcasting\ExpoPushChannel;
use App\Modules\Notification\Models\Notification;

class NotificationObserver
{
    /**
     * Xử lý sau khi thông báo database được tạo.
     *
     * @param Notification $notification Thông báo vừa được tạo
     * @return void
     */
    public function created(Notification $notification): void
    {
        $notifiable = $notification->notifiable;
        if (!$notifiable) {
            return;
        }

        // Bọc data hiện có để tái sử dụng ExpoPushChannel cho thông báo database.
        $fakeNotification = new class($notification->data) extends \Illuminate\Notifications\Notification {
            private array $data;

            public function __construct(array $data)
            {
                $this->data = $data;
            }

            public function via($notifiable): array
            {
                return [];
            }

            public function toArray($notifiable): array
            {
                return $this->data;
            }
        };

        app(ExpoPushChannel::class)->send($notifiable, $fakeNotification);

        event(new \App\Modules\Notification\Events\NotificationCreatedEvent($notification));
    }
}
