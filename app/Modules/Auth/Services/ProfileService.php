<?php

declare(strict_types=1);

namespace App\Modules\Auth\Services;

use App\Core\Services\BaseService;
use App\Core\Services\ServiceReturn;
use App\Modules\Auth\DTO\ChangePasswordDTO;
use App\Modules\Auth\DTO\UpdateProfileDTO;
use App\Modules\Auth\Interfaces\AuthRepositoryInterface;
use Illuminate\Support\Facades\Hash;

/**
 * Handles user profile operations: view, update, change password, list departments.
 */
final class ProfileService extends BaseService
{
    public function __construct(
        private readonly AuthRepositoryInterface $authRepository,
    ) {
    }

    public function getProfile(string $userId): ServiceReturn
    {
        return $this->execute(function () use ($userId) {
            $user = $this->authRepository->findById($userId);

            $this->validate($user !== null, 'Không thể tải thông tin cá nhân. Vui lòng thử lại.', 404);
            $this->validate((bool) $user->is_active, 'Tài khoản của bạn đã bị khóa.', 403);

            $profile = [
                'id' => (string) $user->id,
                'staff_code' => $user->staff_code ?: 'Chưa cập nhật.',
                'name' => $user->name ?: 'Chưa cập nhật.',
                'cccd' => $user->cccd ?: 'Chưa cập nhật.',
                'email' => $user->email ?: 'Chưa cập nhật.',
                'phone' => $user->phone ?: 'Chưa cập nhật.',
                'address' => $user->address ?: 'Chưa cập nhật.',
                'avatar' => $user->avatar,
                'role' => $user->role->serialize(),
                'is_active' => (bool) $user->is_active,
                'created_at' => $user->created_at?->toIso8601String(),
                'branch' => $user->branch,
                'branch_name' => $user->branch,
                'branch_id' => $user->branch_id,
                'department' => $user->department,
                'job_position' => $user->job_position,
            ];

            return $this->success($profile, 'Tải thông tin cá nhân thành công.');
        });
    }

    public function getDepartments(string $userId, ?string $branchId = null): ServiceReturn
    {
        return $this->execute(function () use ($userId, $branchId) {
            $user = $this->authRepository->findById($userId);

            $this->validate($user !== null, 'Không tìm thấy thông tin người dùng.', 404);
            $this->validate((bool) $user->is_active, 'Tài khoản của bạn đã bị khóa.', 403);

            $targetBranchId = $branchId ?: $user->branch_id;

            $departments = collect($this->authRepository->getActiveDepartmentNames($targetBranchId))
                ->map(fn (string $department) => [
                    'label' => $department,
                    'value' => $department,
                ])
                ->values()
                ->all();

            return $this->success([
                'departments' => $departments,
            ], 'Tải danh sách phòng ban thành công.');
        });
    }

    public function updateProfile(UpdateProfileDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto) {
            $user = $this->authRepository->findById($dto->userId);

            $this->validate($user !== null, 'Không thể tải thông tin cá nhân. Vui lòng thử lại.', 404);
            $this->validate((bool) $user->is_active, 'Tài khoản của bạn đã bị khóa.', 403);

            $updated = $this->authRepository->updateById($dto->userId, $dto->toArray());

            $this->validate(
                $updated !== false && $updated !== null,
                'Không thể cập nhật thông tin. Vui lòng thử lại.',
                500
            );

            $profile = [
                'id' => (string) $updated->id,
                'staff_code' => $updated->staff_code ?: 'Chưa cập nhật.',
                'name' => $updated->name ?: 'Chưa cập nhật.',
                'cccd' => $updated->cccd ?: 'Chưa cập nhật.',
                'email' => $updated->email ?: 'Chưa cập nhật.',
                'phone' => $updated->phone ?: 'Chưa cập nhật.',
                'address' => $updated->address ?: 'Chưa cập nhật.',
                'avatar' => $updated->avatar,
                'role' => $updated->role->serialize(),
                'is_active' => (bool) $updated->is_active,
                'created_at' => $updated->created_at?->toIso8601String(),
                'branch' => $updated->branch,
                'branch_name' => $updated->branch,
                'branch_id' => $updated->branch_id,
                'department' => $updated->department,
                'job_position' => $updated->job_position,
            ];

            return $this->success($profile, 'Cập nhật thông tin thành công.');
        }, useTransaction: true);
    }

    public function changePassword(ChangePasswordDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto) {
            $user = $this->authRepository->findById($dto->userId);

            $this->validate($user !== null, 'Không thể tải thông tin cá nhân. Vui lòng thử lại.', 404);
            $this->validate((bool) $user->is_active, 'Tài khoản của bạn đã bị khóa.', 403);
            $this->validate(
                Hash::check($dto->currentPassword, $user->password),
                'Mật khẩu hiện tại không chính xác.',
                400
            );

            $updated = $this->authRepository->updateById($dto->userId, [
                'password' => Hash::make($dto->newPassword),
            ]);

            $this->validate(
                $updated !== false && $updated !== null,
                'Không thể đổi mật khẩu. Vui lòng thử lại.',
                500
            );

            return $this->success(null, 'Đổi mật khẩu thành công.');
        }, useTransaction: true);
    }
}
