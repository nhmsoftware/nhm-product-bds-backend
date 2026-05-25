<?php

namespace App\Modules\Dashboard\Services;

use App\Core\Services\BaseService;
use App\Core\Services\ServiceReturn;
use App\Modules\Auth\Interfaces\AuthRepositoryInterface;
use App\Modules\Auth\Models\Enums\UserRole;
use App\Modules\Dashboard\DTO\ViewDashboardDTO;
use App\Modules\Dashboard\Interfaces\DashboardServiceInterface;
use App\Modules\Attendance\Interfaces\AttendanceRepositoryInterface;
use Carbon\Carbon;

final class DashboardService extends BaseService implements DashboardServiceInterface
{
    public function __construct(
        private readonly AuthRepositoryInterface $authRepository,
        private readonly AttendanceRepositoryInterface $attendanceRepository
    ) {
    }


    /**
     * Lấy dữ liệu tổng quan cho trang chủ (UC-06).
     * 
     * @param ViewDashboardDTO $dto
     * @return ServiceReturn
     * @throws \Throwable
     */
    public function getViewData(ViewDashboardDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto) {
            $user = $this->authRepository->findById($dto->userId);
            $this->validate($user, 'Người dùng không tồn tại.', 404);
            $this->validate($user->is_active, 'Tài khoản của bạn đã bị khóa.', 403);

            // Truy vấn thông tin chấm công thực tế của ngày hôm nay
            $today = Carbon::today()->toDateString();
            $attendance = $this->attendanceRepository->findByUserAndDate($user->id, $today);

            // Mocking data for modules not yet implemented
            $data = [
                'user' => [
                    'id' => (string) $user->id,
                    'name' => $user->name,
                    'avatar' => $user->avatar,
                    'role' => $user->role->serialize(),
                ],
                'overview' => [
                    'latest_news' => $this->getMockNews(),
                    'kpi' => [
                        'points' => 1250,
                        'ranking' => 'Vàng',
                        'next_rank_points' => 1500
                    ],
                ],
                'quick_actions' => [
                    'attendance' => [
                        'has_checked_in' => $attendance !== null,
                        'last_check_in' => $attendance && $attendance->check_in_at ? $attendance->check_in_at->toDateTimeString() : null,
                    ],
                    'application' => [
                        'status' => 'none',
                    ],
                ],
                'modules' => $this->getAuthorizedModules($user->role),
            ];

            return $this->success($data, 'Tải dữ liệu trang chủ thành công.');
        }, useTransaction: false);
    }

    /**
     * Lấy danh sách các module mà user được phép truy cập dựa trên role.
     * 
     * @param UserRole $role
     * @return array
     */
    private function getAuthorizedModules(UserRole $role): array
    {
        $allModules = [
            'lms' => [
                'title' => 'Khóa học',
                'description' => 'Các khóa học đào tạo nội bộ',
                'icon' => 'academic-cap',
                'count' => 3,
                'status' => 'in_progress',
            ],
            'notifications' => [
                'title' => 'Thông báo mới',
                'description' => 'Thông báo từ hệ thống',
                'icon' => 'bell',
                'count' => 5,
            ],
            'warehouse' => [
                'title' => 'Kho hàng',
                'description' => 'Danh sách sản phẩm bất động sản',
                'icon' => 'home',
                'count' => 120,
            ],
            'kpi' => [
                'title' => 'KPI',
                'description' => 'Theo dõi hiệu suất làm việc',
                'icon' => 'chart-bar',
            ],
            'checkin' => [
                'title' => 'Chấm công',
                'description' => 'Điểm danh hằng ngày',
                'icon' => 'location-marker',
            ]
        ];

        // Authorization logic based on UC-06 specifications
        $authorized = [];

        // Default modules for everyone
        $authorized[] = $allModules['notifications'];

        if (in_array($role, [UserRole::SUPER_ADMIN, UserRole::CEO, UserRole::DIRECTOR, UserRole::MANAGER, UserRole::EMPLOYEE], true)) {
            $authorized[] = $allModules['lms'];
            $authorized[] = $allModules['warehouse'];
            $authorized[] = $allModules['kpi'];
            $authorized[] = $allModules['checkin'];
        }

        if ($role === UserRole::BUYER) {
            $authorized[] = $allModules['warehouse'];
        }

        return $authorized;
    }

    /**
     * Dữ liệu giả lập cho tin tức.
     * 
     * @return array
     */
    private function getMockNews(): array
    {
        return [
            [
                'id' => '1',
                'title' => 'Thông báo nghỉ lễ 2/9',
                'summary' => 'Công ty nghỉ lễ từ ngày 01/09 đến hết ngày 03/09...',
                'published_at' => now()->subDays(2)->toDateTimeString(),
            ],
            [
                'id' => '2',
                'title' => 'Khen thưởng nhân viên xuất sắc tháng 4',
                'summary' => 'Chúc mừng anh Nguyễn Văn A đã đạt doanh số cao nhất...',
                'published_at' => now()->subDays(5)->toDateTimeString(),
            ],
        ];
    }
}
