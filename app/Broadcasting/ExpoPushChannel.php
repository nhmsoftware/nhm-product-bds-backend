<?php

declare(strict_types=1);

namespace App\Broadcasting;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ExpoPushChannel
{
    /**
     * Send the given notification.
     *
     * @param mixed $notifiable
     * @param Notification $notification
     * @return void
     */
    public function send($notifiable, Notification $notification): void
    {
        // 1. Kiểm tra notifiable có fcm_token hay không
        if (!isset($notifiable->fcm_token) || empty($notifiable->fcm_token)) {
            return;
        }

        // 2. Lấy dữ liệu payload từ method toExpo hoặc toArray
        if (method_exists($notification, 'toExpo')) {
            $data = $notification->toExpo($notifiable);
        } elseif (method_exists($notification, 'toArray')) {
            $data = $notification->toArray($notifiable);
        } else {
            return;
        }

        $title = $data['title'] ?? 'Thông báo mới';
        $body = $data['body'] ?? 'Bạn có một thông báo mới';
        $actionType = $data['action_type'] ?? null;
        $actionId = $data['action_id'] ?? null;

        $payload = [
            'to' => $notifiable->fcm_token,
            'title' => $title,
            'body' => $body,
            // sound + priority + channelId giúp thông báo hiển thị khi app chạy nền/đóng.
            'sound' => 'default',
            'priority' => 'high',
            'channelId' => 'default',
            'data' => [
                'action_type' => $actionType,
                'action_id' => $actionId,
            ],
        ];

        // 3. Gửi request tới Expo Push Service
        // Nếu bật "Enhanced Security for Push" trên Expo thì cần Access Token.
        $accessToken = config('services.expo.access_token');
        $pushUrl = config('services.expo.push_url', 'https://exp.host/--/api/v2/push/send');

        try {
            $request = Http::acceptJson();
            if (!empty($accessToken)) {
                $request = $request->withToken($accessToken);
            }

            $response = $request->post($pushUrl, $payload);

            if (!$response->successful()) {
                Log::error('Lỗi khi gửi Expo Push Notification:', [
                    'payload' => $payload,
                    'response' => $response->json(),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Exception khi gửi Expo Push Notification:', [
                'message' => $e->getMessage(),
            ]);
        }
    }
}
