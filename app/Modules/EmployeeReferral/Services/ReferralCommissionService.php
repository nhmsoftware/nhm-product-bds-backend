<?php

declare(strict_types=1);

namespace App\Modules\EmployeeReferral\Services;

use App\Core\DTOs\FilterDTO;
use App\Core\Services\BaseService;
use App\Core\Services\ServiceReturn;
use App\Modules\Auth\Interfaces\AuthRepositoryInterface;
use App\Modules\Auth\Models\Enums\UserRole;
use App\Modules\EmployeeReferral\Interfaces\ReferralCommissionRepositoryInterface;
use App\Modules\EmployeeReferral\Interfaces\ReferralCommissionServiceInterface;

final class ReferralCommissionService extends BaseService implements ReferralCommissionServiceInterface
{
    public function __construct(
        private readonly ReferralCommissionRepositoryInterface $commissionRepository,
        private readonly AuthRepositoryInterface $authRepository
    ) {
    }

    /**
     * Lấy danh sách hoa hồng giới thiệu của nhân viên.
     *
     * @param string $userId
     * @param FilterDTO $filter
     * @return ServiceReturn
     */
    public function getCommissions(string $userId, FilterDTO $filter): ServiceReturn
    {
        return $this->execute(function () use ($userId, $filter) {
            $user = $this->authRepository->find($userId);
            $this->validate($user !== null, 'Không tìm thấy thông tin tài khoản người dùng.', 404);

            // A5 - Nhân viên không có quyền truy cập
            $allowedRoles = ['employee', 'tp_kd', 'gdkd', 'ceo', 'super_admin'];
            $this->validate(
                $user->role && in_array($user->role->name, $allowedRoles, true) && ($user->role->name !== 'employee' || !empty($user->job_position)),
                'Bạn không có quyền truy cập chức năng này.',
                403
            );

            // Lấy dữ liệu hoa hồng
            $result = $this->commissionRepository->getCommissions($userId, $filter);
            $paginator = $result['paginator'];
            $totalCommission = $result['total_commission'];

            // Thông báo kịch bản luồng (A1, A2)
            $message = 'Tải dữ liệu hoa hồng referral thành công.';
            if ($paginator->isEmpty()) {
                $appliedFilters = $filter->getFilters();
                if (!empty($appliedFilters)) {
                    $message = 'Không tìm thấy dữ liệu phù hợp.';
                } else {
                    $message = 'Chưa có dữ liệu hoa hồng referral.';
                }
            }

            return $this->success(
                $paginator, 
                $message,
                200,
                ['total_commission' => $totalCommission]
            );
        }, useTransaction: false, returnCatchCallback: function (\Throwable $e) {
            // A4 - Lỗi tải dữ liệu hoa hồng
            return ServiceReturn::error('Không thể tải dữ liệu hoa hồng referral.', 500);
        });
    }

    /**
     * Xem chi tiết một khoản hoa hồng giới thiệu.
     *
     * @param string $userId
     * @param string $commissionId
     * @return ServiceReturn
     */
    public function getDetail(string $userId, string $commissionId): ServiceReturn
    {
        return $this->execute(function () use ($userId, $commissionId) {
            $user = $this->authRepository->find($userId);
            $this->validate($user !== null, 'Không tìm thấy thông tin tài khoản người dùng.', 404);

            $allowedRoles = ['employee', 'tp_kd', 'gdkd', 'ceo', 'super_admin'];
            $this->validate(
                $user->role && in_array($user->role->name, $allowedRoles, true) && ($user->role->name !== 'employee' || !empty($user->job_position)),
                'Bạn không có quyền truy cập chức năng này.',
                403
            );

            $commission = $this->commissionRepository->findCommission($userId, $commissionId);

            // A3 - Referral commission không tồn tại
            $this->validate($commission !== null, 'Thông tin hoa hồng không tồn tại.', 404);

            return $this->success($commission, 'Tải chi tiết referral commission thành công.');
        }, useTransaction: false, returnCatchCallback: function (\Throwable $e) {
            return ServiceReturn::error('Không thể tải chi tiết referral commission.', 500);
        });
    }

    /**
     * Lấy báo cáo hoa hồng referral cho GD hoặc Super Admin.
     *
     * @param \App\Modules\Auth\Models\User $actor
     * @param array $filters
     * @return ServiceReturn
     */
    public function getReport(\App\Modules\Auth\Models\User $actor, array $filters): ServiceReturn
    {
        return $this->execute(function () use ($actor, $filters) {
            $allowedRoles = ['gdkd', 'super_admin', 'ceo'];
            $this->validate(
                $actor->role && in_array($actor->role->name, $allowedRoles, true),
                'Bạn không có quyền truy cập chức năng này.',
                403
            );

            $paginator = $this->commissionRepository->getReport($actor, $filters);

            $message = 'Tải báo cáo hoa hồng referral thành công.';
            if ($paginator->isEmpty()) {
                if (!empty($filters['referrer_id']) || !empty($filters['referral_type']) || !empty($filters['date_from']) || !empty($filters['date_to'])) {
                    $message = 'Không tìm thấy dữ liệu phù hợp.';
                } else {
                    $message = 'Chưa có dữ liệu hoa hồng referral.';
                }
            }

            return $this->success($paginator, $message, 200);
        }, useTransaction: false, returnCatchCallback: function (\Throwable $e) {
            return ServiceReturn::error('Không thể tải báo cáo hoa hồng referral.', 500);
        });
    }
}
