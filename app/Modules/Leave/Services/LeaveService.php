<?php

namespace App\Modules\Leave\Services;

use App\Core\DTOs\FilterDTO;
use App\Core\Services\BaseService;
use App\Core\Services\ServiceReturn;
use App\Modules\Auth\Interfaces\AuthRepositoryInterface;
use App\Modules\Leave\DTO\CreateLeaveDTO;
use App\Modules\Leave\Events\LeaveRequestApproved;
use App\Modules\Leave\Events\LeaveRequestCreated;
use App\Modules\Leave\Events\LeaveRequestRejected;
use App\Modules\Leave\Interfaces\LeaveRequestRepositoryInterface;
use App\Modules\Leave\Interfaces\LeaveServiceInterface;
use App\Modules\Leave\Models\Enums\RequestStatus;
use App\Modules\Auth\Models\Enums\UserRole;

/**
 * Service xử lý toàn bộ logic nghiệp vụ (Business Logic) liên quan đến yêu cầu nghỉ phép.
 */
final class LeaveService extends BaseService implements LeaveServiceInterface
{
    /**
     * Khởi tạo Service và inject các repository tương ứng.
     *
     * @param LeaveRequestRepositoryInterface $leaveRequestRepository
     */
    public function __construct(
        private readonly LeaveRequestRepositoryInterface $leaveRequestRepository,
        private readonly AuthRepositoryInterface $authRepository
    ) {
    }

    /**
     * Tiếp nhận, kiểm tra tính hợp lệ và xử lý lưu yêu cầu xin nghỉ phép mới của nhân viên.
     *
     * @param CreateLeaveDTO $dto DTO chứa thông tin đơn xin nghỉ phép
     * @return ServiceReturn Chứa thông tin đơn đã lưu thành công hoặc thông tin báo lỗi tương ứng
     */
    public function createLeaveRequest(CreateLeaveDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto) {
            // 1. Kiểm tra Preconditions: Tài khoản nhân viên tồn tại trong hệ thống
            $user = $this->authRepository->find($dto->userId);
            $this->validate($user !== null, 'Không tìm thấy thông tin tài khoản người dùng.', 404);

            // 2. Kiểm tra Preconditions: Tài khoản của nhân viên đang hoạt động (không bị khóa)
            $this->validate($user->is_active === true, 'Tài khoản của bạn đã bị khóa hoặc ngừng hoạt động.', 403);

            // 3. Kiểm tra A4: Trùng thời gian nghỉ đã tồn tại
            $isOverlapping = $this->leaveRequestRepository->hasOverlappingLeave(
                $dto->userId,
                $dto->startDate,
                $dto->endDate
            );
            $this->validate(!$isOverlapping, 'Bạn đã có yêu cầu nghỉ phép trong khoảng thời gian này.', 400);

            // 4. Thực hiện lưu yêu cầu nghỉ phép vào hệ thống
            $leaveRequest = $this->leaveRequestRepository->create($dto->toArray());

            // 5. Bắn Domain Event báo gửi đơn nghỉ phép thành công (Dành cho Realtime/Push notifications)
            event(new LeaveRequestCreated($leaveRequest));

            // 6. Trả về kết quả thành công cho Controller
            return $this->success(
                data: $leaveRequest,
                message: 'Gửi yêu cầu nghỉ phép thành công.'
            );
        }, useTransaction: true, returnCatchCallback: function (\Throwable $e) {
            // A5: Xử lý khi xảy ra lỗi hệ thống / lỗi kết nối cơ sở dữ liệu không lưu được
            return ServiceReturn::error(
                message: 'Không thể gửi yêu cầu nghỉ phép. Vui lòng thử lại.',
                code: 500
            );
        });
    }

    /**
     * Tải danh sách lịch sử yêu cầu nghỉ phép của nhân viên có phân trang và lọc.
     *
     * @param string $userId
     * @param FilterDTO $filter
     * @return ServiceReturn
     */
    public function getLeaveHistory(string $userId, FilterDTO $filter): ServiceReturn
    {
        return $this->execute(function () use ($userId, $filter) {
            // 1. Kiểm tra Preconditions: Tài khoản nhân viên tồn tại và đang hoạt động
            $user = $this->authRepository->find($userId);
            $this->validate($user !== null, 'Không tìm thấy thông tin tài khoản người dùng.', 404);
            $this->validate($user->is_active === true, 'Tài khoản của bạn đã bị khóa hoặc ngừng hoạt động.', 403);

            // 2. Lấy dữ liệu lịch sử nghỉ phép
            $history = $this->leaveRequestRepository->getLeaveHistory($userId, $filter);

            // 3. Thiết lập thông báo phù hợp cho từng luồng kịch bản
            $message = 'Tải lịch sử nghỉ phép thành công.';
            if ($history->isEmpty()) {
                $appliedFilters = $filter->getFilters();
                if (!empty($appliedFilters)) {
                    // A3: Không có dữ liệu phù hợp với bộ lọc
                    $message = 'Không có dữ liệu nghỉ phép phù hợp.';
                } else {
                    // A1: Chưa từng gửi đơn xin nghỉ phép nào
                    $message = 'Chưa có lịch sử nghỉ phép.';
                }
            }

            return $this->success(
                data: $history,
                message: $message
            );
        }, useTransaction: false, returnCatchCallback: function (\Throwable $e) {
            // A2: Xử lý khi có lỗi tải dữ liệu hệ thống / CSDL
            return ServiceReturn::error(
                message: 'Không thể tải lịch sử nghỉ phép. Vui lòng thử lại.',
                code: 500
            );
        });
    }

    /**
     * Hủy yêu cầu nghỉ phép đang ở trạng thái chờ duyệt.
     *
     * @param string $userId
     * @param string $leaveRequestId
     * @return ServiceReturn
     */
    public function cancelLeaveRequest(string $userId, string $leaveRequestId): ServiceReturn
    {
        return $this->execute(function () use ($userId, $leaveRequestId) {
            // 1. Kiểm tra Preconditions: Tài khoản nhân viên tồn tại và đang hoạt động
            $user = $this->authRepository->find($userId);
            $this->validate($user !== null, 'Không tìm thấy thông tin tài khoản người dùng.', 404);
            $this->validate($user->is_active === true, 'Tài khoản của bạn đã bị khóa hoặc ngừng hoạt động.', 403);

            // 2. Kiểm tra yêu cầu nghỉ phép tồn tại
            $leaveRequest = $this->leaveRequestRepository->find($leaveRequestId);
            $this->validate($leaveRequest !== null, 'Không tìm thấy thông tin yêu cầu nghỉ phép.', 404);

            // 3. Bảo mật: Yêu cầu nghỉ phép phải thuộc sở hữu của nhân viên đang thao tác
            $this->validate($leaveRequest->user_id === $userId, 'Bạn không có quyền hủy yêu cầu nghỉ phép này.', 403);

            // 4. Kiểm tra trạng thái yêu cầu nghỉ phép
            if ($leaveRequest->status === RequestStatus::APPROVED) {
                // A1: Yêu cầu nghỉ phép đã được duyệt
                $this->validate(false, 'Không thể hủy yêu cầu đã được duyệt.', 400);
            }

            if ($leaveRequest->status === RequestStatus::REJECTED || $leaveRequest->status === RequestStatus::CANCELLED) {
                // A2: Yêu cầu nghỉ phép đã được xử lý trước đó
                $this->validate(false, 'Yêu cầu nghỉ phép đã được xử lý.', 400);
            }

            // 5. Cập nhật trạng thái yêu cầu nghỉ phép thành "cancelled" (Đã hủy)
            $updated = $this->leaveRequestRepository->updateById($leaveRequestId, [
                'status' => RequestStatus::CANCELLED
            ]);

            $this->validate($updated !== false, 'Không thể cập nhật trạng thái yêu cầu nghỉ phép.', 500);

            // 6. Trả về kết quả thành công
            return $this->success(
                data: $updated,
                message: 'Hủy yêu cầu nghỉ phép thành công.'
            );
        }, useTransaction: true, returnCatchCallback: function (\Throwable $e) {
            // A3: Lỗi cập nhật trạng thái hệ thống/CSDL
            return ServiceReturn::error(
                message: 'Không thể hủy yêu cầu nghỉ phép. Vui lòng thử lại.',
                code: 500
            );
        });
    }

    /**
     * Tải danh sách yêu cầu nghỉ phép của nhân viên trong phòng ban (cho Team Leader).
     *
     * @param string $userId ID của Team Leader (Broker hoặc Admin)
     * @param FilterDTO $filter
     * @return ServiceReturn
     */
    public function getDepartmentLeaveRequests(string $userId, FilterDTO $filter): ServiceReturn
    {
        return $this->execute(function () use ($userId, $filter) {
            // 1. Kiểm tra Preconditions: Tài khoản Team Leader tồn tại
            $user = $this->authRepository->find($userId);
            $this->validate($user !== null, 'Không tìm thấy thông tin tài khoản người dùng.', 404);

            // 2. Kiểm tra Preconditions: Tài khoản Team Leader đang hoạt động
            $this->validate($user->is_active === true, 'Tài khoản của bạn đã bị khóa hoặc ngừng hoạt động.', 403);

            // 3. Kiểm tra Preconditions: Quyền quản lý (role phải là broker hoặc admin)
            $this->validate(
                in_array($user->role, [UserRole::MANAGER, UserRole::DIRECTOR, UserRole::CEO, UserRole::SUPER_ADMIN], true),
                'Bạn không có quyền xem danh sách yêu cầu nghỉ phép.',
                403
            );

            // 4. Lấy danh sách yêu cầu nghỉ phép
            $requests = $this->leaveRequestRepository->getDepartmentLeaveRequests($filter);

            // 5. Chuẩn hóa dữ liệu theo đặc tả
            $requests->through(function ($item) {
                $start = \Carbon\Carbon::parse($item->start_date);
                $end = \Carbon\Carbon::parse($item->end_date);
                $numberOfDays = $start->diffInDays($end) + 1;

                return [
                    'id' => (string) $item->id,
                    'user_id' => (string) $item->user_id,
                    'employee_name' => $item->user ? $item->user->name : 'N/A',
                    'department' => 'Phòng Kinh doanh', // Mocked phòng ban do DB chưa có trường phòng ban riêng
                    'leave_type' => $item->leave_type->serialize(),
                    'start_date' => $item->start_date instanceof \Carbon\Carbon ? $item->start_date->toDateString() : $item->start_date,
                    'end_date' => $item->end_date instanceof \Carbon\Carbon ? $item->end_date->toDateString() : $item->end_date,
                    'number_of_days' => $numberOfDays,
                    'reason' => $item->reason,
                    'status' => $item->status->serialize(),
                    'created_at' => $item->created_at ? $item->created_at->toIso8601String() : null,
                ];
            });

            // 6. Thiết lập thông báo phù hợp cho từng luồng kịch bản
            $message = 'Tải danh sách yêu cầu nghỉ phép thành công.';
            if ($requests->isEmpty()) {
                $appliedFilters = $filter->getFilters();
                if (!empty($appliedFilters)) {
                    // A3: Không có dữ liệu phù hợp với bộ lọc
                    $message = 'Không có yêu cầu phù hợp.';
                } else {
                    // A1: Không có yêu cầu nghỉ phép nào
                    $message = 'Chưa có yêu cầu nghỉ phép.';
                }
            }

            return $this->success(
                data: $requests,
                message: $message
            );
        }, useTransaction: false, returnCatchCallback: function (\Throwable $e) {
            // A2: Lỗi tải dữ liệu hệ thống / CSDL
            return ServiceReturn::error(
                message: 'Không thể tải danh sách nghỉ phép. Vui lòng thử lại.',
                code: 500
            );
        });
    }

    /**
     * Phê duyệt đơn xin nghỉ phép của nhân viên trong phòng ban (cho Team Leader) (UC-047).
     *
     * @param string $userId ID của Team Leader thực hiện duyệt
     * @param string $leaveRequestId ID của yêu cầu nghỉ phép cần duyệt
     * @return ServiceReturn
     */
    public function approveLeaveRequest(string $userId, string $leaveRequestId): ServiceReturn
    {
        return $this->execute(function () use ($userId, $leaveRequestId) {
            // 1. Kiểm tra Preconditions: Tài khoản Team Leader tồn tại
            $user = $this->authRepository->find($userId);
            $this->validate($user !== null, 'Không tìm thấy thông tin tài khoản người dùng.', 404);

            // 2. Kiểm tra Preconditions: Tài khoản Team Leader đang hoạt động
            $this->validate($user->is_active === true, 'Tài khoản của bạn đã bị khóa hoặc ngừng hoạt động.', 403);

            // 3. Kiểm tra Preconditions: Quyền duyệt nghỉ phép (role phải là broker hoặc admin)
            $this->validate(
                in_array($user->role, [UserRole::MANAGER, UserRole::DIRECTOR, UserRole::CEO, UserRole::SUPER_ADMIN], true),
                'Bạn không có quyền duyệt nghỉ phép.',
                403
            );

            // 4. Kiểm tra yêu cầu nghỉ phép tồn tại
            $leaveRequest = $this->leaveRequestRepository->find($leaveRequestId);
            $this->validate($leaveRequest !== null, 'Không tìm thấy thông tin yêu cầu nghỉ phép.', 404);

            // 5. Kiểm tra trạng thái yêu cầu nghỉ phép (Phải là 'pending')
            // A1 – Yêu cầu không còn ở trạng thái chờ duyệt
            if ($leaveRequest->status !== RequestStatus::PENDING) {
                $this->validate(false, 'Yêu cầu nghỉ phép đã được xử lý.', 400);
            }

            // 6. Cập nhật trạng thái yêu cầu nghỉ phép thành "approved" (Đã duyệt)
            $updated = $this->leaveRequestRepository->updateById($leaveRequestId, [
                'status' => RequestStatus::APPROVED,
                'approver_id' => $userId,
            ]);

            $this->validate($updated !== false, 'Không thể cập nhật trạng thái yêu cầu nghỉ phép.', 500);

            // 7. Bắn Domain Event báo duyệt thành công để hệ thống gửi thông báo cho nhân viên
            event(new LeaveRequestApproved($updated));

            // 8. Trả về kết quả thành công kèm thông điệp đúng đặc tả
            return $this->success(
                data: $updated,
                message: 'Duyệt đơn nghỉ phép thành công.'
            );
        }, useTransaction: true, returnCatchCallback: function (\Throwable $e) {
            // A3 – Lỗi duyệt yêu cầu
            return ServiceReturn::error(
                message: 'Không thể duyệt yêu cầu. Vui lòng thử lại.',
                code: 500
            );
        });
    }

    /**
     * Từ chối đơn xin nghỉ phép của nhân viên trong phòng ban (cho Team Leader) (UC-048).
     *
     * @param string $userId ID của Team Leader thực hiện từ chối
     * @param string $leaveRequestId ID của yêu cầu nghỉ phép cần từ chối
     * @param string $reason Lý do từ chối
     * @return ServiceReturn
     */
    public function rejectLeaveRequest(string $userId, string $leaveRequestId, string $reason): ServiceReturn
    {
        return $this->execute(function () use ($userId, $leaveRequestId, $reason) {
            // 1. Kiểm tra Preconditions: Tài khoản Team Leader tồn tại
            $user = $this->authRepository->find($userId);
            $this->validate($user !== null, 'Không tìm thấy thông tin tài khoản người dùng.', 404);

            // 2. Kiểm tra Preconditions: Tài khoản Team Leader đang hoạt động
            $this->validate($user->is_active === true, 'Tài khoản của bạn đã bị khóa hoặc ngừng hoạt động.', 403);

            // 3. Kiểm tra Preconditions: Quyền duyệt nghỉ phép (role phải là broker hoặc admin)
            $this->validate(
                in_array($user->role, [UserRole::MANAGER, UserRole::DIRECTOR, UserRole::CEO, UserRole::SUPER_ADMIN], true),
                'Bạn không có quyền từ chối nghỉ phép.',
                403
            );

            // 4. Kiểm tra yêu cầu nghỉ phép tồn tại
            $leaveRequest = $this->leaveRequestRepository->find($leaveRequestId);
            $this->validate($leaveRequest !== null, 'Không tìm thấy thông tin yêu cầu nghỉ phép.', 404);

            // 5. Kiểm tra trạng thái yêu cầu nghỉ phép (Phải là 'pending')
            // A2 – Yêu cầu không còn ở trạng thái chờ duyệt
            if ($leaveRequest->status !== RequestStatus::PENDING) {
                $this->validate(false, 'Yêu cầu nghỉ phép đã được xử lý.', 400);
            }

            // 6. Cập nhật trạng thái yêu cầu nghỉ phép thành "rejected" (Từ chối) và lưu lý do
            $updated = $this->leaveRequestRepository->updateById($leaveRequestId, [
                'status' => RequestStatus::REJECTED,
                'rejection_reason' => $reason
            ]);

            $this->validate($updated !== false, 'Không thể cập nhật trạng thái yêu cầu nghỉ phép.', 500);

            // 7. Bắn Domain Event báo từ chối thành công để hệ thống gửi thông báo cho nhân viên
            event(new LeaveRequestRejected($updated));

            // 8. Trả về kết quả thành công kèm thông điệp đúng đặc tả
            return $this->success(
                data: $updated,
                message: 'Từ chối đơn nghỉ phép thành công.'
            );
        }, useTransaction: true, returnCatchCallback: function (\Throwable $e) {
            // A4 – Lỗi từ chối yêu cầu
            return ServiceReturn::error(
                message: 'Không thể từ chối yêu cầu. Vui lòng thử lại.',
                code: 500
            );
        });
    }
}
