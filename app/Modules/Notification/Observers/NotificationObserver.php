<?php

declare(strict_types=1);

namespace App\Modules\Notification\Observers;

use App\Broadcasting\ExpoPushChannel;
use App\Modules\Notification\Models\Notification;

class NotificationObserver
{
    /**
     * Handle the Notification "created" event.
     *
     * @param Notification $notification
     * @return void
     */
    public function created(Notification $notification): void
    {
        // Khi một database notification được tạo, tự động gửi qua Expo Push
        
        $notifiable = $notification->notifiable;
        if (!$notifiable) {
            return;
        }

        // Tạo một object notification giả lập để tương thích với ExpoPushChannel
        // Vì ExpoPushChannel kỳ vọng nhận vào đối tượng Illuminate\Notifications\Notification
        // Ở đây ta bọc lại data của database notification.
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

        // Gửi push notification thông qua ExpoPushChannel
        app(ExpoPushChannel::class)->send($notifiable, $fakeNotification);

        // Gửi realtime qua Redis -> Socket.io
        event(new \App\Modules\Notification\Events\NotificationCreatedEvent($notification));
    }
}
