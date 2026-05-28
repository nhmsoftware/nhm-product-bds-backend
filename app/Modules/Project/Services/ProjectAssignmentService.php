<?php

declare(strict_types=1);

namespace App\Modules\Project\Services;

use App\Core\DTOs\ServiceReturn;
use App\Core\Services\BaseService;
use App\Modules\Auth\Models\Enums\UserRole;
use App\Modules\Auth\Interfaces\AuthRepositoryInterface;
use App\Modules\Project\DTO\AssignPermissionDTO;
use App\Modules\Project\Interfaces\ProjectAssignmentRepositoryInterface;
use App\Modules\Project\Interfaces\ProjectAssignmentServiceInterface;
use App\Modules\Project\Interfaces\ProjectRepositoryInterface;

class ProjectAssignmentService extends BaseService implements ProjectAssignmentServiceInterface
{
    public function __construct(
        private readonly ProjectAssignmentRepositoryInterface $assignmentRepository,
        private readonly ProjectRepositoryInterface $projectRepository,
        private readonly AuthRepositoryInterface $authRepository
    ) {}

    public function assignPermission(string $userId, string $projectId, AssignPermissionDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($userId, $projectId, $dto) {
            $user = $this->authRepository->findById($userId);
            $this->validate($user !== null, 'Không tìm thấy thông tin người dùng.', 404);

            $project = $this->projectRepository->findById($projectId);
            $this->validate($project !== null, 'Dự án không tồn tại.', 404);

            // General Director chỉ được phân quyền Project chi nhánh của bản thân
            if ($user->role === UserRole::DIRECTOR) {
                $this->validate($project->branch === $user->department, 'Bạn không có quyền thực hiện chức năng này trên dự án của chi nhánh khác.', 403);
            }

            // Kiểm tra assignableId có tồn tại không
            if ($dto->assignableType === 'user') {
                $assignee = $this->authRepository->findById($dto->assignableId) !== null;
                $this->validate($assignee, 'Người dùng không tồn tại.', 404);
            } else {
                $assignee = $this->assignmentRepository->checkActiveDepartmentExists($dto->assignableId);
                $this->validate($assignee, 'Phòng ban không tồn tại.', 404);
            }

            // Tìm phân quyền cũ
            $assignment = $this->assignmentRepository->findAssignment($projectId, $dto->assignableId, $dto->assignableType);

            if ($assignment) {
                // Kiểm tra xem quyền có giống nhau không
                $currentPermissions = $assignment->permissions ?? [];
                if (count($currentPermissions) === count($dto->permissions) && empty(array_diff($currentPermissions, $dto->permissions))) {
                    $this->throw('Đối tượng này đã được cấp các quyền tương tự cho dự án này.', 400);
                }

                $updated = $this->assignmentRepository->updateById((string)$assignment->id, [
                    'permissions' => $dto->permissions
                ]);
                return $this->success($updated, 'Cấp quyền inventory thành công.');
            }

            // Tạo mới
            $newAssignment = $this->assignmentRepository->create([
                'project_id' => $projectId,
                'assignable_id' => $dto->assignableId,
                'assignable_type' => $dto->assignableType,
                'permissions' => $dto->permissions,
            ]);

            return $this->success($newAssignment, 'Cấp quyền inventory thành công.', 201);

        }, useTransaction: true, returnCatchCallback: function (\Throwable $e) {
            if ($e instanceof \App\Core\Services\ServiceException) {
                return ServiceReturn::error($e->getMessage(), $e->getCode());
            }
            return ServiceReturn::error('Không thể cập nhật phân quyền.', 500);
        });
    }
}
