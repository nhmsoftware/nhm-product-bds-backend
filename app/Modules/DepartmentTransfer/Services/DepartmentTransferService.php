<?php

namespace App\Modules\DepartmentTransfer\Services;

use App\Core\DTOs\FilterDTO;
use App\Core\Services\ServiceReturn;
use App\Core\Services\BaseService;
use App\Modules\DepartmentTransfer\DTO\StoreDepartmentTransferRequestDTO;
use App\Modules\DepartmentTransfer\Events\DepartmentTransferRequestCreated;
use App\Modules\DepartmentTransfer\Interfaces\DepartmentTransferRequestRepositoryInterface;
use App\Modules\DepartmentTransfer\Interfaces\DepartmentTransferServiceInterface;
use Exception;
use Illuminate\Support\Facades\Log;
use App\Modules\DepartmentTransfer\Models\Enums\RequestStatus;
use App\Modules\Auth\Models\Enums\UserRole;

class DepartmentTransferService extends BaseService implements DepartmentTransferServiceInterface
{
    protected DepartmentTransferRequestRepositoryInterface $departmentTransferRequestRepository;

    public function __construct(DepartmentTransferRequestRepositoryInterface $departmentTransferRequestRepository)
    {
        $this->departmentTransferRequestRepository = $departmentTransferRequestRepository;
    }

    /**
     * Tạo yêu cầu chuyển phòng ban.
     *
     * @param StoreDepartmentTransferRequestDTO $dto
     * @return ServiceReturn
     */
    public function createDepartmentTransferRequest(StoreDepartmentTransferRequestDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto) {
            // 1. Kiểm tra Preconditions: Tài khoản nhân viên tồn tại trong hệ thống
            $user = \App\Modules\Auth\Models\User::find($dto->userId);
            $this->validate($user !== null, 'Không tìm thấy thông tin tài khoản người dùng.', 404);

            // 2. Kiểm tra Preconditions: Tài khoản của nhân viên đang hoạt động (không bị khóa)
            $this->validate($user->is_active === true, 'Tài khoản của bạn đã bị khóa hoặc ngừng hoạt động.', 403);

            // 3. Kiểm tra xem đã có yêu cầu pending nào không
            $hasPending = $this->departmentTransferRequestRepository->hasPendingRequest($dto->userId);
            $this->validate(!$hasPending, 'Bạn đang có yêu cầu chuyển phòng ban chờ xử lý.', 400);

            // 4. Kiểm tra phòng ban mục tiêu có trùng phòng ban hiện tại không
            $this->validate($dto->currentDepartment !== $dto->targetDepartment, 'Phòng ban muốn chuyển không được trùng với phòng ban hiện tại.', 400);

            // 5. Kiểm tra ngày chuyển hợp lệ (phải >= hôm nay)
            $this->validate(strtotime($dto->desiredTransferDate) >= strtotime(date('Y-m-d')), 'Ngày chuyển phòng ban không hợp lệ.', 400);

            // 6. Thực hiện lưu yêu cầu chuyển phòng ban vào hệ thống
            $data = [
                'user_id' => $dto->userId,
                'current_department' => $dto->currentDepartment,
                'target_department' => $dto->targetDepartment,
                'reason' => $dto->reason,
                'desired_transfer_date' => $dto->desiredTransferDate,
                'status' => RequestStatus::PENDING,
            ];

            $transferRequest = $this->departmentTransferRequestRepository->create($data);

            // 7. Bắn Domain Event báo gửi đơn thành công
            event(new DepartmentTransferRequestCreated($transferRequest));

            // 8. Trả về kết quả thành công cho Controller
            return $this->success(
                data: $transferRequest,
                message: 'Gửi yêu cầu chuyển phòng ban thành công.'
            );
        }, useTransaction: true, returnCatchCallback: function (\Throwable $e) {
            // Xử lý khi xảy ra lỗi hệ thống / lỗi kết nối cơ sở dữ liệu không lưu được
            return ServiceReturn::error(
                message: 'Không thể gửi yêu cầu chuyển phòng ban. Vui lòng thử lại.',
                code: 500
            );
        });
    }

    /**
     * Lấy danh sách yêu cầu chuyển phòng ban của nhân viên có phân trang và lọc (UC-050).
     *
     * @param string $userId
     * @param FilterDTO $filter
     * @return ServiceReturn
     */
    public function getDepartmentTransferRequests(string $userId, FilterDTO $filter): ServiceReturn
    {
        return $this->execute(function () use ($userId, $filter) {
            // 1. Kiểm tra Preconditions: Tài khoản Director/Admin tồn tại
            $user = \App\Modules\Auth\Models\User::find($userId);
            $this->validate($user !== null, 'Không tìm thấy thông tin tài khoản người dùng.', 404);

            // 2. Kiểm tra Preconditions: Tài khoản Director/Admin đang hoạt động
            $this->validate($user->is_active === true, 'Tài khoản của bạn đã bị khóa hoặc ngừng hoạt động.', 403);

            // 3. Kiểm tra Preconditions: Quyền xem yêu cầu chuyển phòng ban (chỉ Director hoặc Admin)
            $this->validate(
                in_array($user->role, [UserRole::DIRECTOR, UserRole::CEO, UserRole::SUPER_ADMIN], true),
                'Bạn không có quyền xem yêu cầu chuyển phòng ban.',
                403
            );

            // 4. Lấy danh sách yêu cầu chuyển phòng ban
            $requests = $this->departmentTransferRequestRepository->getTransferRequests($filter);

            // 5. Chuẩn hóa dữ liệu theo đặc tả
            $requests->through(function ($item) {
                return [
                    'id' => (string) $item->id,
                    'user_id' => (string) $item->user_id,
                    'employee_name' => $item->user ? $item->user->name : 'N/A',
                    'current_department' => $item->current_department,
                    'target_department' => $item->target_department,
                    'desired_transfer_date' => $item->desired_transfer_date instanceof \Carbon\Carbon 
                        ? $item->desired_transfer_date->toDateString() 
                        : ($item->desired_transfer_date ? date('Y-m-d', strtotime($item->desired_transfer_date)) : null),
                    'reason' => $item->reason,
                    'status' => $item->status->serialize(),
                    'created_at' => $item->created_at ? $item->created_at->toIso8601String() : null,
                ];
            });

            // 6. Thiết lập thông báo phù hợp cho từng luồng kịch bản
            $message = 'Tải danh sách yêu cầu chuyển phòng ban thành công.';
            if ($requests->isEmpty()) {
                $appliedFilters = $filter->getFilters();
                if (!empty($appliedFilters)) {
                    // A3: Không có dữ liệu phù hợp với bộ lọc
                    $message = 'Không có yêu cầu phù hợp.';
                } else {
                    // A1: Không có yêu cầu chuyển phòng ban
                    $message = 'Chưa có yêu cầu chuyển phòng ban.';
                }
            }

            return $this->success(
                data: $requests,
                message: $message
            );
        }, useTransaction: false, returnCatchCallback: function (\Throwable $e) {
            // A2: Lỗi tải dữ liệu hệ thống / CSDL
            return ServiceReturn::error(
                message: 'Không thể tải dữ liệu yêu cầu chuyển phòng ban.',
                code: 500
            );
        });
    }

    /**
     * Phê duyệt yêu cầu chuyển phòng ban của nhân viên (UC-051).
     *
     * @param string $userId ID của Director thực hiện duyệt
     * @param string $requestId ID của yêu cầu chuyển phòng ban cần duyệt
     * @return ServiceReturn
     */
    public function approveDepartmentTransferRequest(string $userId, string $requestId): ServiceReturn
    {
        return $this->execute(function () use ($userId, $requestId) {
            // 1. Kiểm tra Preconditions: Tài khoản Director/Admin tồn tại
            $user = \App\Modules\Auth\Models\User::find($userId);
            $this->validate($user !== null, 'Không tìm thấy thông tin tài khoản người dùng.', 404);

            // 2. Kiểm tra Preconditions: Tài khoản Director/Admin đang hoạt động
            $this->validate($user->is_active === true, 'Tài khoản của bạn đã bị khóa hoặc ngừng hoạt động.', 403);

            // 3. Kiểm tra Preconditions: Quyền duyệt chuyển phòng ban (role phải là admin)
            $this->validate(
                in_array($user->role, [UserRole::DIRECTOR, UserRole::CEO, UserRole::SUPER_ADMIN], true),
                'Bạn không có quyền duyệt yêu cầu chuyển phòng ban.',
                403
            );

            // 4. Kiểm tra yêu cầu chuyển phòng ban tồn tại
            $transferRequest = $this->departmentTransferRequestRepository->find($requestId);
            $this->validate($transferRequest !== null, 'Không tìm thấy yêu cầu chuyển phòng ban.', 404);

            // 5. Kiểm tra trạng thái yêu cầu chuyển phòng ban
            // A1 – Yêu cầu đã được xử lý trước đó
            if ($transferRequest->status !== RequestStatus::PENDING) {
                $this->validate(false, 'Yêu cầu đã được xử lý.', 400);
            }

            // 6. Thực hiện cập nhật trạng thái yêu cầu thành "approved" (Đã duyệt)
            $updated = $this->departmentTransferRequestRepository->updateById($requestId, [
                'status' => RequestStatus::APPROVED
            ]);

            $this->validate($updated !== false, 'Không thể cập nhật trạng thái yêu cầu.', 500);

            // 7. Bắn Domain Event báo duyệt thành công để hệ thống gửi thông báo cho nhân viên
            event(new \App\Modules\DepartmentTransfer\Events\DepartmentTransferRequestApproved($updated));

            // 8. Trả về kết quả thành công kèm thông điệp đúng đặc tả
            return $this->success(
                data: $updated,
                message: 'Duyệt yêu cầu chuyển phòng ban thành công.'
            );
        }, useTransaction: true, returnCatchCallback: function (\Throwable $e) {
            // A3 – Lỗi cập nhật dữ liệu
            return ServiceReturn::error(
                message: 'Không thể duyệt yêu cầu chuyển phòng ban. Vui lòng thử lại.',
                code: 500
            );
        });
    }

    /**
     * Từ chối yêu cầu chuyển phòng ban của nhân viên (UC-052).
     *
     * @param string $userId ID của Director thực hiện từ chối
     * @param string $requestId ID của yêu cầu chuyển phòng ban cần từ chối
     * @param string $reason Lý do từ chối yêu cầu
     * @return ServiceReturn
     */
    public function rejectDepartmentTransferRequest(string $userId, string $requestId, string $reason): ServiceReturn
    {
        return $this->execute(function () use ($userId, $requestId, $reason) {
            // 1. Kiểm tra Preconditions: Tài khoản Director/Admin tồn tại
            $user = \App\Modules\Auth\Models\User::find($userId);
            $this->validate($user !== null, 'Không tìm thấy thông tin tài khoản người dùng.', 404);

            // 2. Kiểm tra Preconditions: Tài khoản Director/Admin đang hoạt động
            $this->validate($user->is_active === true, 'Tài khoản của bạn đã bị khóa hoặc ngừng hoạt động.', 403);

            // 3. Kiểm tra Preconditions: Quyền từ chối chuyển phòng ban (role phải là admin)
            $this->validate(
                in_array($user->role, [UserRole::DIRECTOR, UserRole::CEO, UserRole::SUPER_ADMIN], true),
                'Bạn không có quyền từ chối yêu cầu chuyển phòng ban.',
                403
            );

            // 4. Kiểm tra yêu cầu chuyển phòng ban tồn tại
            $transferRequest = $this->departmentTransferRequestRepository->find($requestId);
            $this->validate($transferRequest !== null, 'Không tìm thấy yêu cầu chuyển phòng ban.', 404);

            // 5. Kiểm tra trạng thái yêu cầu chuyển phòng ban
            // A2 – Yêu cầu đã được xử lý trước đó
            if ($transferRequest->status !== RequestStatus::PENDING) {
                $this->validate(false, 'Yêu cầu đã được xử lý.', 400);
            }

            // A1 – Director chưa nhập lý do từ chối
            $this->validate(!empty(trim($reason)), 'Vui lòng nhập lý do từ chối.', 422);

            // 6. Thực hiện cập nhật trạng thái yêu cầu thành "rejected" (Từ chối) và lưu lý do
            $updated = $this->departmentTransferRequestRepository->updateById($requestId, [
                'status' => RequestStatus::REJECTED,
                'rejection_reason' => $reason
            ]);

            $this->validate($updated !== false, 'Không thể cập nhật trạng thái yêu cầu.', 500);

            // 7. Bắn Domain Event báo từ chối thành công để hệ thống gửi thông báo cho nhân viên
            event(new \App\Modules\DepartmentTransfer\Events\DepartmentTransferRequestRejected($updated));

            // 8. Trả về kết quả thành công kèm thông điệp đúng đặc tả
            return $this->success(
                data: $updated,
                message: 'Từ chối yêu cầu chuyển phòng ban thành công.'
            );
        }, useTransaction: true, returnCatchCallback: function (\Throwable $e) {
            // A4 – Lỗi cập nhật dữ liệu
            return ServiceReturn::error(
                message: 'Không thể từ chối yêu cầu chuyển phòng ban. Vui lòng thử lại.',
                code: 500
            );
        });
    }
}


