<?php

declare(strict_types=1);

namespace App\Modules\EmployeeReferral\Services;

use App\Core\DTOs\FilterDTO;
use App\Core\Services\BaseService;
use App\Core\Services\ServiceReturn;
use App\Modules\Auth\Interfaces\AuthRepositoryInterface;
use App\Modules\Auth\Models\Enums\UserRole;
use App\Modules\EmployeeReferral\DTO\ScanReferralDTO;
use App\Modules\EmployeeReferral\Interfaces\ReferralHistoryRepositoryInterface;
use App\Modules\EmployeeReferral\Interfaces\ReferralHistoryServiceInterface;
use App\Modules\EmployeeReferral\Models\Enums\ReferralStatus;

final class ReferralHistoryService extends BaseService implements ReferralHistoryServiceInterface
{
    public function __construct(
        private readonly ReferralHistoryRepositoryInterface $referralHistoryRepository,
        private readonly AuthRepositoryInterface $authRepository
    ) {
    }

    /**
     * Ghi nhận lượt quét mã QR giới thiệu (Public).
     *
     * @param ScanReferralDTO $dto
     * @return ServiceReturn
     * @throws \Throwable
     */
    public function recordScan(ScanReferralDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto) {
            // 1. Tìm nhân viên theo referral_code (staff_code, REC-staff_code hoặc CUS-staff_code)
            $employee = $this->authRepository->findByStaffCode($dto->referral_code);
            $this->validate($employee !== null, 'Mã giới thiệu không hợp lệ hoặc không tồn tại.', 404);
            $this->validate($employee->is_active === true, 'Tài khoản nhân viên giới thiệu đã bị khóa.', 403);

            // 2. Kiểm tra xem người dùng đã có lượt quét QR từ nhân viên này với sđt tương tự chưa
            $existingRecord = $this->referralHistoryRepository->findByReferrerAndPhone($employee->id, $dto->phone);

            if ($existingRecord) {
                // Nếu có rồi, cập nhật lại thời gian quét mới nhất và tên nếu có thay đổi
                $updated = $this->referralHistoryRepository->updateById($existingRecord->id, [
                    'name' => $dto->name,
                    'scanned_at' => now(),
                ]);
                $this->validate($updated !== false, 'Không thể cập nhật lượt quét QR.', 500);

                return $this->success($updated, 'Cập nhật lượt quét QR thành công.');
            }

            // 3. Nếu chưa có, tạo bản ghi mới
            $newRecord = $this->referralHistoryRepository->create([
                'referrer_id' => $employee->id,
                'name' => $dto->name,
                'phone' => $dto->phone,
                'referral_type' => $dto->referral_type,
                'status' => ReferralStatus::INCOMPLETE->value,
                'scanned_at' => now(),
            ]);

            return $this->success($newRecord, 'Lưu lượt quét QR thành công.', 201);
        }, useTransaction: true);
    }

    /**
     * Lấy danh sách lịch sử giới thiệu của nhân viên.
     *
     * @param string $userId
     * @param FilterDTO $filter
     * @return ServiceReturn
     */
    public function getHistory(string $userId, FilterDTO $filter): ServiceReturn
    {
        return $this->execute(function () use ($userId, $filter) {
            // 1. Kiểm tra tài khoản nhân viên
            $user = $this->authRepository->find($userId);
            $this->validate($user !== null, 'Không tìm thấy thông tin tài khoản người dùng.', 404);

            // A5 - Nhân viên không có quyền truy cập
            $allowedRoles = [UserRole::EMPLOYEE, UserRole::MANAGER, UserRole::DIRECTOR, UserRole::CEO, UserRole::SUPER_ADMIN];
            $this->validate(
                in_array($user->role, $allowedRoles, true) && ($user->role !== UserRole::EMPLOYEE || !empty($user->job_position)),
                'Bạn không có quyền truy cập chức năng này.',
                403
            );

            // 2. Lấy dữ liệu lịch sử giới thiệu
            $history = $this->referralHistoryRepository->getHistory($userId, $filter);

            // 3. Thông báo kịch bản luồng (A1, A2)
            $message = 'Tải lịch sử referral thành công.';
            if ($history->isEmpty()) {
                $appliedFilters = $filter->getFilters();
                if (!empty($appliedFilters)) {
                    $message = 'Không tìm thấy dữ liệu phù hợp.';
                } else {
                    $message = 'Chưa có lịch sử referral.';
                }
            }

            return $this->success($history, $message);
        }, useTransaction: false, returnCatchCallback: function (\Throwable $e) {
            // A4 - Lỗi tải dữ liệu referral
            return ServiceReturn::error('Không thể tải lịch sử referral.', 500);
        });
    }

    /**
     * Xem chi tiết một bản ghi giới thiệu.
     *
     * @param string $userId
     * @param string $referralId
     * @return ServiceReturn
     */
    public function getDetail(string $userId, string $referralId): ServiceReturn
    {
        return $this->execute(function () use ($userId, $referralId) {
            // 1. Kiểm tra tài khoản
            $user = $this->authRepository->find($userId);
            $this->validate($user !== null, 'Không tìm thấy thông tin tài khoản người dùng.', 404);

            $allowedRoles = [UserRole::EMPLOYEE, UserRole::MANAGER, UserRole::DIRECTOR, UserRole::CEO, UserRole::SUPER_ADMIN];
            $this->validate(
                in_array($user->role, $allowedRoles, true) && ($user->role !== UserRole::EMPLOYEE || !empty($user->job_position)),
                'Bạn không có quyền truy cập chức năng này.',
                403
            );

            // 2. Lấy chi tiết referral
            $referral = $this->referralHistoryRepository->findById($referralId, ['*'], ['referee']);

            // A3 - Referral không tồn tại
            $this->validate($referral !== null, 'Thông tin referral không tồn tại.', 404);

            // Kiểm tra sở hữu (chỉ cho phép chính nhân viên đó hoặc admin xem)
            if ($user->role !== UserRole::SUPER_ADMIN) {
                $this->validate(
                    $referral->referrer_id === $userId,
                    'Bạn không có quyền truy cập chức năng này.',
                    403
                );
            }

            return $this->success($referral, 'Tải chi tiết referral thành công.');
        }, useTransaction: false, returnCatchCallback: function (\Throwable $e) {
            return ServiceReturn::error('Không thể tải chi tiết referral.', 500);
        });
    }
}
