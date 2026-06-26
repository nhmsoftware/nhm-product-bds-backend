<?php

declare(strict_types=1);

namespace App\Modules\Notification\Interfaces;

use App\Core\Services\ServiceReturn;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Interface định nghĩa các thao tác tương tác với bảng notifications trong CSDL.
 */
interface NotificationRepositoryInterface
{
    /**
     * Lấy danh sách thông báo của người dùng có phân trang.
     * Sắp xếp theo thời gian mới nhất, ưu tiên thông báo chưa đọc.
     *
     * @param string      $userId  UUID của người dùng cần lấy thông báo
     * @param int         $perPage Số lượng thông báo trên mỗi trang
     * @param int         $page    Trang hiện tại
     * @param bool|null   $isRead  Lọc theo trạng thái đọc (null = tất cả, true = đã đọc, false = chưa đọc)
     * @return LengthAwarePaginator Danh sách thông báo có phân trang
     */
    public function getNotifications(string $userId, int $perPage, int $page, ?bool $isRead): LengthAwarePaginator;

    /**
     * Tìm một thông báo cụ thể theo ID.
     *
     * @param string $notificationId UUID của thông báo
     * @return DatabaseNotification|null Đối tượng thông báo hoặc null nếu không tìm thấy
     */
    public function findById(string $notificationId): ?DatabaseNotification;

    /**
     * Đánh dấu một thông báo là đã đọc bằng cách cập nhật trường read_at.
     *
     * @param DatabaseNotification $notification Đối tượng thông báo cần cập nhật
     * @return DatabaseNotification Đối tượng thông báo sau khi cập nhật
     */
    public function markAsRead(DatabaseNotification $notification): DatabaseNotification;

    /**
     * Đánh dấu tất cả thông báo chưa đọc của người dùng là đã đọc.
     *
     * @param string $userId UUID của người dùng
     * @return int Số lượng thông báo được cập nhật
     */
    public function markAllAsRead(string $userId): int;

    /**
     * Đếm số lượng thông báo chưa đọc của người dùng.
     *
     * @param string $userId UUID của người dùng
     * @return int Số thông báo chưa đọc
     */
    public function countUnread(string $userId): int;

    /**
     * Tạo thông báo database cho một người dùng.
     *
     * @param string $type   Loại thông báo (Laravel notification class name hoặc custom type string)
     * @param string $userId UUID của người nhận thông báo
     * @param array<string, mixed> $data Payload thông báo
     * @return DatabaseNotification Thông báo vừa tạo
     */
    public function createForUser(string $type, string $userId, array $data): DatabaseNotification;
}
