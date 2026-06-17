<?php

declare(strict_types=1);

namespace App\Modules\News\Listeners;

use App\Modules\Auth\Interfaces\AuthRepositoryInterface;
use App\Modules\News\Events\InternalPostCreated;
use App\Modules\Notification\Interfaces\NotificationRepositoryInterface;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Tạo thông báo database cho người dùng cùng phòng ban hoặc cùng khu vực khi có bài viết nội bộ mới.
 */
final class CreateNotificationsForInternalPost implements ShouldQueue
{
    /**
     * Khởi tạo listener với các repository cần thiết.
     *
     * @param AuthRepositoryInterface $authRepository Repository thao tác dữ liệu người dùng
     * @param NotificationRepositoryInterface $notificationRepository Repository thao tác dữ liệu thông báo
     */
    public function __construct(
        private readonly AuthRepositoryInterface $authRepository,
        private readonly NotificationRepositoryInterface $notificationRepository,
    ) {
    }

    /**
     * Xử lý sự kiện bài viết nội bộ vừa được tạo.
     *
     * @param InternalPostCreated $event Sự kiện bài viết nội bộ vừa được tạo
     * @return void
     */
    public function handle(InternalPostCreated $event): void
    {
        $news = $event->news;

        $recipients = $this->authRepository->getActiveUsersForInternalPost(
            department: $news->department,
            branchId: $news->branch_id,
            authorId: (string) $news->author_id
        );

        foreach ($recipients as $recipient) {
            $this->notificationRepository->createForUser((string) $recipient->id, [
                'title' => 'Có bài viết nội bộ mới',
                'body' => $news->title,
                'user_id' => (string) $recipient->id,
                'notifiable_id' => (string) $recipient->id,
                'action_type' => 'internal_post',
                'action_id' => (string) $news->id,
            ]);
        }
    }
}
