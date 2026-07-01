<?php

declare(strict_types=1);

namespace App\Modules\Auth\Services;

use App\Core\Services\BaseService;
use App\Core\Services\ServiceReturn;
use App\Modules\Auth\DTO\GetTeamMembersDTO;
use App\Modules\Auth\Interfaces\AuthRepositoryInterface;
use App\Modules\Auth\Models\Enums\UserRole;

/**
 * Handles team management: overview, member listing.
 */
final class TeamService extends BaseService
{
    private const TEAM_ALLOWED_ROLES = [
        'tp_kd',
        'gdkd',
        'ceo',
        'super_admin',
    ];

    public function __construct(
        private readonly AuthRepositoryInterface $authRepository,
    ) {
    }

    public function getTeamOverview(string $userId): ServiceReturn
    {
        return $this->execute(function () use ($userId) {
            $user = $this->authRepository->findById($userId);

            $this->validate($user !== null, 'Không tìm thấy thông tin người dùng.', 404);
            $this->validate(
                $user->role && in_array($user->role->name, self::TEAM_ALLOWED_ROLES, true),
                'Bạn không có quyền truy cập chức năng này.',
                403
            );

            $teamName = match ($user->role?->name) {
                'tp_kd' => $user->department,
                'gdkd' => $user->area,
                'ceo' => 'Toàn công ty',
                'super_admin' => 'Toàn hệ thống',
                default => '',
            };

            $memberCount = $this->authRepository->countActiveTeamMembers($user);

            $overview = [
                'team_name' => $teamName ?: 'Chưa cập nhật',
                'description' => 'Phòng ban/Khu vực ' . ($teamName ?: 'Chưa cập nhật'),
                'member_count' => $memberCount,
                'manager_name' => $user->name,
            ];

            return $this->success($overview, 'Tải thông tin tổng quan thành công.');
        });
    }

    public function getTeamMembers(GetTeamMembersDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto) {
            $user = $this->authRepository->findById($dto->userId);

            $this->validate($user !== null, 'Không tìm thấy thông tin người dùng.', 404);
            $this->validate(
                $user->role && in_array($user->role->name, self::TEAM_ALLOWED_ROLES, true),
                'Bạn không có quyền truy cập chức năng này.',
                403
            );

            $members = $this->authRepository->getActiveTeamMembers(
                $user,
                $dto->search,
                $dto->jobPosition,
                $dto->perPage
            );

            if ($members->isEmpty() && !$dto->search && !$dto->jobPosition) {
                return $this->success($members, 'Chưa có nhân viên trong phòng ban.');
            }

            if ($members->isEmpty()) {
                return $this->success($members, 'Không tìm thấy nhân viên phù hợp.');
            }

            $members->getCollection()->transform(fn ($member) => [
                'id' => (string) $member->id,
                'staff_code' => $member->staff_code,
                'name' => $member->name,
                'job_position' => $member->job_position,
                'phone' => $member->phone,
                'avatar' => $member->avatar,
            ]);

            return $this->success($members, 'Tải danh sách nhân viên thành công.');
        });
    }
}
