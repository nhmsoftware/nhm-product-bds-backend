<?php

declare(strict_types=1);

namespace App\Modules\Notification\Services;

use App\Core\Services\BaseService;
use App\Core\Services\ServiceReturn;
use App\Modules\Auth\Interfaces\AuthRepositoryInterface;
use App\Modules\Notification\Interfaces\NotificationRepositoryInterface;
use App\Modules\Notification\Interfaces\NotificationServiceInterface;

/**
 * Service xử lý toàn bộ logic nghiệp vụ liên quan đến thông báo của người dùng.
 */
final class NotificationService extends BaseService implements NotificationServiceInterface
{
    /**
     * Khởi tạo Service và inject các repository cần thiết.
     *
     * @param NotificationRepositoryInterface $notificationRepository Repository thao tác với bảng notifications
     * @param AuthRepositoryInterface         $authRepository         Repository thao tác với bảng users
     */
    public function __construct(
        private readonly NotificationRepositoryInterface $notificationRepository,
        private readonly AuthRepositoryInterface $authRepository,
    ) {
    }

    /**
     * Lấy danh sách thông báo của người dùng có phân trang và lọc theo trạng thái đọc.
     *
     * @param string    $userId  UUID của người dùng
     * @param int       $perPage Số lượng thông báo mỗi trang
     * @param int       $page    Trang hiện tại
     * @param bool|null $isRead  Lọc trạng thái (null = tất cả, true = đã đọc, false = chưa đọc)
     * @return ServiceReturn Chứa danh sách thông báo hoặc thông báo lỗi
     */
    public function getNotifications(string $userId, int $perPage, int $page, ?bool $isRead): ServiceReturn
    {
        return $this->execute(function () use ($userId, $perPage, $page, $isRead) {
            // 1. Kiểm tra Preconditions: Người dùng tồn tại và đang hoạt động
            $user = $this->authRepository->find($userId);
            $this->validate($user !== null, 'Không tìm thấy thông tin tài khoản người dùng.', 404);
            $this->validate($user->is_active === true, 'Tài khoản của bạn đã bị khóa hoặc ngừng hoạt động.', 403);

            // 2. Tải danh sách thông báo từ CSDL
            $notifications = $this->notificationRepository->getNotifications($userId, $perPage, $page, $isRead);

            // 3. Đếm số thông báo chưa đọc để trả về metadata
            $unreadCount = $this->notificationRepository->countUnread($userId);

            // 4. Xác định thông điệp trả về phù hợp với tình huống (A1: không có thông báo)
            $message = 'Tải danh sách thông báo thành công.';
            if ($notifications->isEmpty()) {
                $message = 'Hiện chưa có thông báo nào.';
            }

            // 5. Trả về kết quả thành công kèm metadata
            return $this->success(
                data: [
                    'notifications' => $notifications,
                    'unread_count'  => $unreadCount,
                ],
                message: $message
            );
        }, useTransaction: false, returnCatchCallback: function (\Throwable $e) {
            // A2: Lỗi tải dữ liệu thông báo
            return ServiceReturn::error(
                message: 'Không thể tải thông báo. Vui lòng thử lại.',
                code: 500
            );
        });
    }

    /**
     * Đánh dấu một thông báo cụ thể là đã đọc.
     * Nếu thông báo đã được đọc trước đó (A3), trạng thái được giữ nguyên.
     *
     * @param string $userId         UUID của người dùng thực hiện thao tác
     * @param string $notificationId UUID của thông báo cần đánh dấu
     * @return ServiceReturn Chứa thông báo sau khi cập nhật hoặc lỗi
     */
    public function markAsRead(string $userId, string $notificationId): ServiceReturn
    {
        return $this->execute(function () use ($userId, $notificationId) {
            // 1. Kiểm tra Preconditions: Người dùng tồn tại và đang hoạt động
            $user = $this->authRepository->find($userId);
            $this->validate($user !== null, 'Không tìm thấy thông tin tài khoản người dùng.', 404);
            $this->validate($user->is_active === true, 'Tài khoản của bạn đã bị khóa hoặc ngừng hoạt động.', 403);

            // 2. Tìm thông báo theo ID (A4: Thông báo không còn tồn tại)
            $notification = $this->notificationRepository->findById($notificationId);
            $this->validate($notification !== null, 'Thông báo không tồn tại hoặc đã bị xóa.', 404);

            // 3. Bảo mật: Kiểm tra thông báo thuộc về người dùng đang thao tác
            $this->validate(
                $notification->notifiable_id === $userId,
                'Bạn không có quyền truy cập thông báo này.',
                403
            );

            // 4. Đánh dấu đã đọc (nếu đã đọc rồi thì giữ nguyên - A3)
            $updated = $this->notificationRepository->markAsRead($notification);

            return $this->success(
                data: $updated,
                message: 'Đã đánh dấu thông báo là đã đọc.'
            );
        }, useTransaction: false, returnCatchCallback: function (\Throwable $e) {
            return ServiceReturn::error(
                message: 'Không thể cập nhật trạng thái thông báo. Vui lòng thử lại.',
                code: 500
            );
        });
    }

    /**
     * Đánh dấu tất cả thông báo chưa đọc của người dùng là đã đọc.
     *
     * @param string $userId UUID của người dùng
     * @return ServiceReturn Chứa số lượng thông báo được cập nhật
     */
    public function markAllAsRead(string $userId): ServiceReturn
    {
        return $this->execute(function () use ($userId) {
            // 1. Kiểm tra Preconditions: Người dùng tồn tại và đang hoạt động
            $user = $this->authRepository->find($userId);
            $this->validate($user !== null, 'Không tìm thấy thông tin tài khoản người dùng.', 404);
            $this->validate($user->is_active === true, 'Tài khoản của bạn đã bị khóa hoặc ngừng hoạt động.', 403);

            // 2. Cập nhật tất cả thông báo chưa đọc
            $updatedCount = $this->notificationRepository->markAllAsRead($userId);

            return $this->success(
                data: ['updated_count' => $updatedCount],
                message: 'Đã đánh dấu tất cả thông báo là đã đọc.'
            );
        }, useTransaction: true, returnCatchCallback: function (\Throwable $e) {
            return ServiceReturn::error(
                message: 'Không thể cập nhật trạng thái thông báo. Vui lòng thử lại.',
                code: 500
            );
        });
    }

    /**
     * Lấy chi tiết một thông báo và tự động đánh dấu là đã đọc (A5 – UC-131).
     * Đây là luồng khi người dùng mở nội dung chi tiết thông báo trực tiếp.
     * Nếu thông báo đã đọc trước đó (A1), trạng thái được giữ nguyên.
     *
     * @param string $userId         UUID của người dùng thực hiện
     * @param string $notificationId UUID của thông báo cần xem
     * @return ServiceReturn Chứa thông tin chi tiết thông báo kèm trạng thái đã đọc
     */
    public function getNotificationDetail(string $userId, string $notificationId): ServiceReturn
    {
        return $this->execute(function () use ($userId, $notificationId) {
            // 1. Kiểm tra Preconditions: Người dùng tồn tại và đang hoạt động
            $user = $this->authRepository->find($userId);
            $this->validate($user !== null, 'Không tìm thấy thông tin tài khoản người dùng.', 404);
            $this->validate($user->is_active === true, 'Tài khoản của bạn đã bị khóa hoặc ngừng hoạt động.', 403);

            // 2. Tìm thông báo theo ID (A2: Thông báo không tồn tại hoặc đã bị xóa)
            $notification = $this->notificationRepository->findById($notificationId);
            $this->validate($notification !== null, 'Thông báo không tồn tại hoặc đã bị xóa.', 404);

            // 3. Bảo mật: Kiểm tra thông báo thuộc về người dùng đang thao tác
            $this->validate(
                $notification->notifiable_id === $userId,
                'Bạn không có quyền xem thông báo này.',
                403
            );

            // 4. A5: Tự động đánh dấu đã đọc khi mở chi tiết thông báo
            // Nếu đã đọc trước đó (A1), giữ nguyên trạng thái, không ghi đè read_at
            $notification = $this->notificationRepository->markAsRead($notification);

            // 5. Đếm lại số thông báo chưa đọc sau khi cập nhật (để Frontend cập nhật badge)
            $unreadCount = $this->notificationRepository->countUnread($userId);

            return $this->success(
                data: [
                    'notification' => $notification,
                    'unread_count' => $unreadCount,
                ],
                message: 'Tải chi tiết thông báo thành công.'
            );
        }, useTransaction: false, returnCatchCallback: function (\Throwable $e) {
            // A3: Lỗi cập nhật trạng thái thông báo
            return ServiceReturn::error(
                message: 'Không thể cập nhật trạng thái thông báo. Vui lòng thử lại.',
                code: 500
            );
        });
    }
}
