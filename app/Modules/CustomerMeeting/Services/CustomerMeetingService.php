<?php

namespace App\Modules\CustomerMeeting\Services;

use App\Core\Services\BaseService;
use App\Core\Services\ServiceReturn;
use App\Modules\Auth\Interfaces\AuthRepositoryInterface;
use App\Modules\CustomerMeeting\DTO\CheckInMeetCustomerDTO;
use App\Modules\CustomerMeeting\Events\CustomerMeetCheckedIn;
use App\Modules\CustomerMeeting\Interfaces\CustomerMeetingRepositoryInterface;
use App\Modules\CustomerMeeting\Interfaces\CustomerMeetingServiceInterface;
use App\Modules\Project\Interfaces\ProjectRepositoryInterface;
use Illuminate\Support\Facades\Storage;
use App\Modules\Auth\Models\Enums\UserRole;

final class CustomerMeetingService extends BaseService implements CustomerMeetingServiceInterface
{
    public function __construct(
        private readonly CustomerMeetingRepositoryInterface $customerMeetingRepository,
        private readonly AuthRepositoryInterface $authRepository,
        private readonly ProjectRepositoryInterface $projectRepository
    ) {
    }

    /**
     * Thực hiện check-in hoạt động gặp khách hàng tại dự án.
     *
     * @param CheckInMeetCustomerDTO $dto Dữ liệu check-in gặp khách
     * @return ServiceReturn Trả về kết quả lưu hoạt động gặp khách thành công hoặc thất bại
     */
    public function checkInMeetCustomer(CheckInMeetCustomerDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto) {
            // 1. Kiểm tra tài khoản và phân quyền
            $user = $this->authRepository->findById($dto->userId);
            
            $this->validate(
                $user !== null,
                'Không thể xác minh thông tin tài khoản.',
                404
            );

            // Kiểm tra tài khoản bị khóa / không hoạt động
            $this->validate(
                (bool) $user->is_active,
                'Tài khoản của bạn đã bị khóa hoặc đang ngưng hoạt động.',
                403
            );

            // Kiểm tra role: Nhân viên có quyền sử dụng chức năng (admin, agent, broker)
            $this->validate(
                in_array($user->role, [UserRole::SUPER_ADMIN, UserRole::CEO, UserRole::DIRECTOR, UserRole::MANAGER, UserRole::EMPLOYEE], true),
                'Bạn không có quyền thực hiện chức năng này.',
                403
            );

            // 2. Kiểm tra dự án tồn tại
            $project = $this->projectRepository->findById($dto->projectId);
            $this->validate(
                $project !== null,
                'Dự án quan tâm không tồn tại trên hệ thống.',
                404
            );

            // 3. Lưu ảnh thực tế vào thư mục public/meetings
            $imagePath = $dto->image->store('meetings', 'public');
            $this->validate(
                $imagePath !== false && $imagePath !== null,
                'Không thể tải ảnh thực tế lên hệ thống. Vui lòng thử lại.',
                500
            );

            // 4. Tạo bản ghi hoạt động gặp khách hàng
            $meetingData = $dto->toArray();
            $meetingData['image_path'] = Storage::url($imagePath);

            $meeting = $this->customerMeetingRepository->create($meetingData);
            
            $this->validate(
                $meeting !== null,
                'Không thể lưu hoạt động gặp khách. Vui lòng thử lại.',
                500
            );

            // 5. Bắn Domain Event
            event(new CustomerMeetCheckedIn($meeting));

            // Lấy lại danh sách hoạt động gần đây của nhân viên để cập nhật danh sách
            $recentMeetings = $this->customerMeetingRepository->getRecentMeetingsByUserId($dto->userId, 5);

            return $this->success([
                'meeting' => $meeting,
                'recent_meetings' => $recentMeetings
            ], 'Check-in gặp khách thành công.', 201);
        }, useTransaction: true);
    }

    /**
     * Lấy danh sách các hoạt động gặp khách hàng gần đây của nhân viên.
     *
     * @param string $userId ID của nhân viên (UUID)
     * @param int $limit Số lượng bản ghi tối đa
     * @return ServiceReturn Trả về danh sách các hoạt động
     */
    public function getRecentMeetings(string $userId, int $limit = 5): ServiceReturn
    {
        return $this->execute(function () use ($userId, $limit) {
            $user = $this->authRepository->findById($userId);
            $this->validate($user !== null, 'Tài khoản không tồn tại.', 404);
            $this->validate((bool) $user->is_active, 'Tài khoản của bạn đã bị khóa hoặc đang ngưng hoạt động.', 403);

            try {
                $meetings = $this->customerMeetingRepository->getRecentMeetingsByUserId($userId, $limit);

                if ($meetings->isEmpty()) {
                    return $this->success($meetings, 'Chưa có hoạt động gần đây.');
                }

                return $this->success($meetings, 'Tải danh sách hoạt động gần đây thành công.');
            } catch (\Exception $e) {
                return $this->error('Không thể tải hoạt động gần đây. Vui lòng thử lại.', 500);
            }
        });
    }

    /**
     * Lấy chi tiết hoạt động gặp khách hàng.
     *
     * @param string $userId ID của nhân viên (UUID)
     * @param string $id ID của hoạt động gặp khách hàng (UUID)
     * @return ServiceReturn Trả về chi tiết hoạt động gặp khách hàng
     */
    public function getMeetingDetails(string $userId, string $id): ServiceReturn
    {
        return $this->execute(function () use ($userId, $id) {
            $user = $this->authRepository->findById($userId);
            $this->validate($user !== null, 'Tài khoản không tồn tại.', 404);
            $this->validate((bool) $user->is_active, 'Tài khoản của bạn đã bị khóa hoặc đang ngưng hoạt động.', 403);

            $meeting = $this->customerMeetingRepository->findById($id);

            // A3 - Hoạt động không tồn tại hoặc đã bị xóa
            $this->validate(
                $meeting !== null && $meeting->user_id === $userId,
                'Hoạt động không tồn tại hoặc đã bị xóa.',
                404
            );

            return $this->success($meeting, 'Tải chi tiết hoạt động thành công.');
        });
    }
}
