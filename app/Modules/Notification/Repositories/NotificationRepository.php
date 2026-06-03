<?php

declare(strict_types=1);

namespace App\Modules\Notification\Repositories;

use App\Modules\Notification\Interfaces\NotificationRepositoryInterface;
use App\Modules\Notification\Models\Notification;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * Repository thực thi các thao tác CSDL cho bảng notifications.
 *
 * Không kế thừa từ BaseRepository vì DatabaseNotification của Laravel
 * không dùng cơ chế tạo model thông thường (newQuery từ table trực tiếp).
 */
final class NotificationRepository implements NotificationRepositoryInterface
{
    /**
     * Lấy danh sách thông báo của người dùng có phân trang.
     * Sắp xếp: chưa đọc lên trước, sau đó theo thời gian mới nhất.
     *
     * @param string    $userId  UUID của người dùng
     * @param int       $perPage Số lượng bản ghi mỗi trang
     * @param int       $page    Trang hiện tại
     * @param bool|null $isRead  Lọc theo trạng thái đọc (null = tất cả)
     * @return LengthAwarePaginator Danh sách thông báo phân trang
     */
    public function getNotifications(string $userId, int $perPage, int $page, ?bool $isRead): LengthAwarePaginator
    {
        $query = Notification::where('notifiable_type', \App\Modules\Auth\Models\User::class)
            ->where('notifiable_id', $userId);

        // A5: Lọc theo trạng thái đọc/chưa đọc nếu có
        if ($isRead === true) {
            $query->whereNotNull('read_at');
        } elseif ($isRead === false) {
            $query->whereNull('read_at');
        }

        // Sắp xếp: chưa đọc (read_at IS NULL) lên trước, sau đó theo created_at mới nhất
        return $query
            ->orderByRaw('CASE WHEN read_at IS NULL THEN 1 ELSE 0 END DESC')
            ->orderByDesc('created_at')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Tìm một thông báo cụ thể theo ID.
     *
     * @param string $notificationId UUID của thông báo
     * @return DatabaseNotification|null
     */
    public function findById(string $notificationId): ?DatabaseNotification
    {
        return Notification::find($notificationId);
    }

    /**
     * Đánh dấu một thông báo là đã đọc.
     * Nếu thông báo đã có read_at, không ghi đè.
     *
     * @param DatabaseNotification $notification Đối tượng thông báo
     * @return DatabaseNotification Thông báo sau khi cập nhật
     */
    public function markAsRead(DatabaseNotification $notification): DatabaseNotification
    {
        if ($notification->read_at === null) {
            $notification->markAsRead();
        }

        return $notification;
    }

    /**
     * Đánh dấu tất cả thông báo chưa đọc của người dùng là đã đọc.
     *
     * @param string $userId UUID của người dùng
     * @return int Số lượng thông báo được cập nhật
     */
    public function markAllAsRead(string $userId): int
    {
        return DB::table('notifications')
            ->where('notifiable_type', \App\Modules\Auth\Models\User::class)
            ->where('notifiable_id', $userId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    /**
     * Đếm số lượng thông báo chưa đọc của người dùng.
     *
     * @param string $userId UUID của người dùng
     * @return int Tổng số thông báo chưa đọc
     */
    public function countUnread(string $userId): int
    {
        return Notification::where('notifiable_type', \App\Modules\Auth\Models\User::class)
            ->where('notifiable_id', $userId)
            ->whereNull('read_at')
            ->count();
    }
}
