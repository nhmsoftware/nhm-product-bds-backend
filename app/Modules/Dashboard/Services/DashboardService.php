<?php

namespace App\Modules\Dashboard\Services;

use App\Core\Services\BaseService;
use App\Core\Services\ServiceReturn;
use App\Modules\Auth\Interfaces\AuthRepositoryInterface;
use App\Modules\Auth\Models\User;
use App\Modules\Auth\Models\Enums\UserRole;
use App\Modules\Area\Models\Enums\LotStatus;
use App\Modules\Area\Models\Lot;
use App\Modules\Dashboard\DTO\ViewDashboardDTO;
use App\Modules\Dashboard\Interfaces\DashboardServiceInterface;
use App\Modules\Attendance\Interfaces\AttendanceRepositoryInterface;
use App\Modules\Learning\Models\Course;
use App\Modules\News\Models\News;
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
            $user = $this->authRepository->findById($dto->userId, ['*'], ['employeeProfile']);
            $this->validate($user !== null, 'Người dùng không tồn tại.', 404);
            $this->validate((bool) $user->is_active, 'Tài khoản của bạn đã bị khóa.', 403);

            // Truy vấn thông tin chấm công thực tế của ngày hôm nay
            $today = Carbon::today()->toDateString();
            $attendance = $this->attendanceRepository->findByUserAndDate($user->id, $today);

            $latestNews = $this->getLatestInternalNews($user);
            $totalPoints = (int) ($user->employeeProfile?->reward_points ?? 0);
            $rank = $this->resolveRewardRank($totalPoints);
            $moduleCounts = [
                'news' => count($latestNews),
                'courses' => $this->countAvailableCourses($user),
                'warehouse' => $this->countAvailableLots($user),
            ];

            $data = [
                'user' => [
                    'id' => (string) $user->id,
                    'name' => $user->name,
                    'avatar' => $user->avatar,
                    'role' => $user->role->serialize(),
                ],
                'overview' => [
                    'latest_news' => $latestNews,
                    'kpi' => [
                        'points' => $totalPoints,
                        'kpi_stars' => (int) ($user->employeeProfile?->kpi_stars ?? 0),
                        'rank' => $rank,
                        'next_rank_points' => $this->getNextRankPoints($totalPoints),
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
                'modules' => $this->getAuthorizedModules($user->role, $moduleCounts),
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
    private function getAuthorizedModules(UserRole $role, array $moduleCounts = []): array
    {
        $newsCount = (int) ($moduleCounts['news'] ?? 0);
        $courseCount = (int) ($moduleCounts['courses'] ?? 0);
        $warehouseCount = (int) ($moduleCounts['warehouse'] ?? 0);

        $allModules = [
            'lms' => [
                'title' => 'Khóa học',
                'description' => 'Các khóa học đào tạo nội bộ',
                'icon' => 'academic-cap',
                'count' => $courseCount,
                'status' => 'in_progress',
            ],
            'notifications' => [
                'title' => 'Thông báo mới',
                'description' => 'Thông báo từ hệ thống',
                'icon' => 'bell',
                'count' => $newsCount,
            ],
            'warehouse' => [
                'title' => 'Kho hàng',
                'description' => 'Danh sách sản phẩm bất động sản',
                'icon' => 'home',
                'count' => $warehouseCount,
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

    private function getLatestInternalNews(User $user): array
    {
        $query = News::query()->where('is_published', true);

        if (in_array($user->role, [UserRole::EMPLOYEE, UserRole::MANAGER], true)) {
            $query->where('department', $user->department);
        } elseif ($user->role === UserRole::DIRECTOR) {
            $query->where('area', $user->area);
        } elseif (!in_array($user->role, [UserRole::CEO, UserRole::SUPER_ADMIN], true)) {
            $query->whereRaw('1 = 0');
        }

        return $query
            ->orderByDesc('published_at')
            ->limit(5)
            ->get(['id', 'title', 'summary', 'published_at'])
            ->map(fn (News $news) => [
                'id' => (string) $news->id,
                'title' => $news->title,
                'summary' => $news->summary,
                'published_at' => $news->published_at?->toDateTimeString(),
            ])
            ->all();
    }

    private function countAvailableCourses(User $user): int
    {
        return Course::query()
            ->where('is_active', true)
            ->where(function ($query) use ($user) {
                $query->where('is_required', true);

                if (!empty($user->department)) {
                    $query->orWhere('department', $user->department);
                }

                if (!empty($user->job_position)) {
                    $query->orWhere('job_position', $user->job_position);
                }
            })
            ->count();
    }

    private function countAvailableLots(User $user): int
    {
        $query = Lot::query()
            ->where('lots.status', LotStatus::AVAILABLE->value);

        if (!in_array($user->role, [UserRole::DIRECTOR, UserRole::CEO, UserRole::SUPER_ADMIN], true)) {
            $query
                ->join('area_assignments', 'lots.area_id', '=', 'area_assignments.area_id')
                ->where('area_assignments.user_id', $user->id)
                ->whereNull('area_assignments.deleted_at')
                ->select('lots.*');
        }

        return $query->count();
    }

    private function resolveRewardRank(int $totalPoints): array
    {
        if ($totalPoints >= 1500) {
            return ['id' => 4, 'label' => 'Bạch kim'];
        }

        if ($totalPoints >= 800) {
            return ['id' => 3, 'label' => 'Vàng'];
        }

        if ($totalPoints >= 500) {
            return ['id' => 2, 'label' => 'Bạc'];
        }

        return ['id' => 1, 'label' => 'Đồng'];
    }

    private function getNextRankPoints(int $totalPoints): ?int
    {
        foreach ([500, 800, 1500] as $threshold) {
            if ($totalPoints < $threshold) {
                return $threshold;
            }
        }

        return null;
    }
}
