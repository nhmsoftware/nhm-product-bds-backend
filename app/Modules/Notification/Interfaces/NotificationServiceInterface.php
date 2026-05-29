<?php

declare(strict_types=1);

namespace App\Modules\Notification\Interfaces;

use App\Core\Services\ServiceReturn;

/**
 * Interface định nghĩa các phương thức xử lý nghiệp vụ thông báo.
 */
interface NotificationServiceInterface
{
    /**
     * Lấy danh sách thông báo của người dùng đang đăng nhập, có phân trang và lọc.
     *
     * @param string    $userId  UUID của người dùng
     * @param int       $perPage Số lượng thông báo trên mỗi trang
     * @param int       $page    Trang hiện tại
     * @param bool|null $isRead  Bộ lọc trạng thái đọc (null = tất cả, true = đã đọc, false = chưa đọc)
     * @return ServiceReturn Chứa danh sách thông báo phân trang hoặc lỗi
     * @throws \App\Core\Services\ServiceException
     */
    public function getNotifications(string $userId, int $perPage, int $page, ?bool $isRead): ServiceReturn;

    /**
     * Đánh dấu một thông báo cụ thể là đã đọc.
     * Nếu thông báo đã đọc trước đó, trạng thái được giữ nguyên.
     *
     * @param string $userId         UUID của người dùng đang thực hiện (để xác thực quyền sở hữu)
     * @param string $notificationId UUID của thông báo cần đánh dấu
     * @return ServiceReturn Chứa thông báo sau khi cập nhật hoặc lỗi
     * @throws \App\Core\Services\ServiceException
     */
    public function markAsRead(string $userId, string $notificationId): ServiceReturn;

    /**
     * Đánh dấu tất cả thông báo chưa đọc của người dùng là đã đọc.
     *
     * @param string $userId UUID của người dùng
     * @return ServiceReturn Chứa số lượng thông báo đã được cập nhật
     * @throws \App\Core\Services\ServiceException
     */
    public function markAllAsRead(string $userId): ServiceReturn;

    /**
     * Lấy chi tiết một thông báo và tự động đánh dấu là đã đọc (A5 – UC-131).
     * Nếu thông báo đã đọc trước đó, trạng thái được giữ nguyên.
     * Nếu thông báo không tồn tại hoặc không thuộc người dùng, trả về lỗi tương ứng.
     *
     * @param string $userId         UUID của người dùng đang thực hiện
     * @param string $notificationId UUID của thông báo cần xem chi tiết
     * @return ServiceReturn Chứa thông tin chi tiết thông báo (đã tự động đánh dấu đọc) hoặc lỗi
     * @throws \App\Core\Services\ServiceException
     */
    public function getNotificationDetail(string $userId, string $notificationId): ServiceReturn;
}
