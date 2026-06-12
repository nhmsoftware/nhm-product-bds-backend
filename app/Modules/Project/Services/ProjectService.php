<?php

namespace App\Modules\Project\Services;

use App\Core\Services\ServiceReturn;
use App\Core\Services\BaseService;
use App\Modules\Project\DTO\ProjectListDTO;
use App\Modules\Project\DTO\CreateProjectDTO;
use App\Modules\Project\DTO\UpdateProjectDTO;
use App\Modules\Project\DTO\ListAdminProjectDTO;
use App\Modules\Project\DTO\BulkCreateProjectDTO;
use App\Modules\Auth\Interfaces\AuthRepositoryInterface;
use App\Modules\Auth\Models\Enums\UserRole;
use App\Modules\Project\Interfaces\ProjectRepositoryInterface;
use App\Modules\Project\Interfaces\ProjectServiceInterface;
use App\Modules\Project\Models\Project;
use App\Modules\Area\Interfaces\AreaServiceInterface;

/**
 * Class ProjectService
 * 
 * @package App\Modules\Project\Services
 */
final class ProjectService extends BaseService implements ProjectServiceInterface
{
    /**
     * @param ProjectRepositoryInterface $projectRepository
     * @param AreaServiceInterface $areaService
     */
    public function __construct(
        private readonly ProjectRepositoryInterface $projectRepository,
        private readonly AreaServiceInterface $areaService,
        private readonly AuthRepositoryInterface $authRepository
    ) {
    }

    /**
     * Lấy danh sách dự án công khai.
     * 
     * @param ProjectListDTO $dto
     * @return ServiceReturn
     */
    public function getPublicList(ProjectListDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto) {
            $projects = $this->projectRepository->listPublic($dto);
            $projects->setCollection(
                $projects->getCollection()->map(fn (Project $project) => $this->publicProjectPayload($project))
            );

            $message = $projects->total() > 0 
                ? 'Tải danh sách dự án thành công.' 
                : 'Không tìm thấy dự án phù hợp.';

            return $this->success(
                $projects,
                $message
            );
        }, useTransaction: false);
    }

    /**
     * Lấy chi tiết dự án công khai.
     * 
     * @param string $id
     * @return ServiceReturn
     */
    public function getPublicDetail(string $id): ServiceReturn
    {
        return $this->execute(function () use ($id) {
            $project = $this->projectRepository->findPublicById($id);

            $this->validate($project !== null, 'Dự án không tồn tại hoặc đã bị xóa.', 404);

            return $this->success(
                $this->publicProjectPayload($project),
                'Tải chi tiết dự án thành công.'
            );
        }, useTransaction: false);
    }

    /**
     * Tìm kiếm dự án công khai.
     * 
     * @param string $keyword
     * @param int $perPage
     * @param int $page
     * @return ServiceReturn
     */
    public function searchProjects(string $keyword, int $perPage = 10, int $page = 1): ServiceReturn
    {
        return $this->execute(function () use ($keyword, $perPage, $page) {
            $projects = $this->projectRepository->searchPublic($keyword, $perPage, $page);
            $projects->setCollection(
                $projects->getCollection()->map(fn (Project $project) => $this->publicProjectPayload($project))
            );

            $message = $projects->total() > 0 
                ? 'Tìm thấy ' . $projects->total() . ' dự án phù hợp.' 
                : 'Không tìm thấy dự án phù hợp.';

            return $this->success(
                $projects,
                $message
            );
        }, useTransaction: false);
    }

    private function publicProjectPayload(Project $project): array
    {
        $payload = $project->toArray();
        $payload['banner'] = $this->bannerList($payload['banner'] ?? null);

        return $payload;
    }

    private function bannerList(mixed $banner): array
    {
        if (is_array($banner)) {
            return array_values(array_filter(array_map(
                fn (mixed $item) => is_string($item) ? trim($item) : '',
                $banner
            )));
        }

        if (is_string($banner) && trim($banner) !== '') {
            return [trim($banner)];
        }

        return [];
    }

    /**
     * Lấy thông tin brochure của dự án.
     * 
     * @param string $id
     * @return ServiceReturn
     */
    public function getBrochure(string $id): ServiceReturn
    {
        return $this->execute(function () use ($id) {
            $project = $this->projectRepository->findPublicById($id);

            $this->validate($project !== null, 'Dự án không tồn tại hoặc đã bị xóa.', 404);
            $this->validate(!empty($project->brochure), 'Brochure đang được cập nhật.', 404);

            return $this->success(
                [
                    'url' => $project->brochure,
                    'project_name' => $project->name
                ],
                'Tải brochure thành công.'
            );
        }, useTransaction: false);
    }

    /**
     * Lấy số hotline tư vấn của dự án.
     * 
     * @param string $id
     * @return ServiceReturn
     */
    public function getHotline(string $id): ServiceReturn
    {
        return $this->execute(function () use ($id) {
            $project = $this->projectRepository->findPublicById($id);

            $this->validate($project !== null, 'Dự án không tồn tại hoặc đã bị xóa.', 404);
            
            $hotline = $project->contact_info['hotline'] ?? null;
            $this->validate(!empty($hotline), 'Hotline tư vấn hiện chưa khả dụng.', 404);

            return $this->success(
                ['hotline' => $hotline],
                'Lấy số hotline thành công.'
            );
        }, useTransaction: false);
    }

    /**
     * [Admin] Lấy danh sách dự án.
     */
    public function getAdminProjects(string $userId, ListAdminProjectDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto) {
            $branch = null;
            if ($dto->userRole === UserRole::DIRECTOR) {
                $branch = $dto->userBranch; // General Director chỉ xem dự án của chi nhánh mình
            }

            $projects = $this->projectRepository->listAdminProjects($dto, $branch);

            return $this->success(
                $projects,
                'Tải danh sách dự án thành công.'
            );
        }, useTransaction: false);
    }

    /**
     * [Admin] Lấy chi tiết dự án (kèm sơ đồ bảng hàng).
     */
    public function getProjectDetailAdmin(string $userId, string $id): ServiceReturn
    {
        return $this->execute(function () use ($id) {
            $project = $this->projectRepository->findById($id);
            $this->validate($project !== null, 'Dự án không tồn tại.', 404);

            $project->load('areas.lots');

            return $this->success(
                $project,
                'Tải chi tiết dự án thành công.'
            );
        }, useTransaction: false);
    }

    /**
     * [Admin] Tạo dự án mới.
     */
    public function createProject(string $userId, CreateProjectDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto) {
            $project = $this->projectRepository->create($dto->toArray());

            return $this->success(
                $project,
                'Tạo dự án thành công.',
                201
            );
        }, useTransaction: true);
    }

    /**
     * [Admin] Cập nhật dự án.
     */
    public function updateProject(string $userId, string $id, UpdateProjectDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($id, $dto) {
            $project = $this->projectRepository->findById($id);
            $this->validate($project !== null, 'Dự án không tồn tại.', 404);

            $updatedProject = $this->projectRepository->updateById($id, $dto->toArray());

            if ($dto->areas !== null) {
                $this->areaService->bulkSyncAreasWithLots($id, $dto->areas);
            }

            return $this->success(
                $updatedProject,
                'Cập nhật dự án thành công.'
            );
        }, useTransaction: true);
    }

    /**
     * [Admin] Khóa/Mở khóa dự án.
     */
    public function lockUnlockProject(string $userId, string $id, bool $isLocked): ServiceReturn
    {
        return $this->execute(function () use ($userId, $id, $isLocked) {
            $user = $this->authRepository->find($userId);
            $this->validate($user !== null, 'Không tìm thấy thông tin người dùng.', 404);

            $project = $this->projectRepository->findById($id);
            $this->validate($project !== null, 'Dự án không tồn tại.', 404);

            // General Director chỉ được Lock/Unlock Project chi nhánh của bản thân
            if ($user->role === UserRole::DIRECTOR) {
                $this->validate($project->branch === $user->department, 'Bạn không có quyền thực hiện chức năng này trên dự án của chi nhánh khác.', 403);
            }

            // Kiểm tra trạng thái hiện tại
            if ($isLocked && $project->is_locked) {
                $this->throw('Dự án đã được khóa.', 400);
            }
            if (!$isLocked && !$project->is_locked) {
                $this->throw('Dự án đang hoạt động.', 400);
            }

            $updatedProject = $this->projectRepository->updateById($id, ['is_locked' => $isLocked]);

            $message = $isLocked ? 'Khóa dự án thành công.' : 'Cập nhật trạng thái dự án thành công.';

            return $this->success(
                $updatedProject,
                $message
            );
        }, useTransaction: true, returnCatchCallback: function (\Throwable $e) {
            if ($e instanceof \App\Core\Services\ServiceException) {
                return ServiceReturn::error($e->getMessage(), $e->getCode());
            }
            return ServiceReturn::error('Không thể cập nhật trạng thái dự án.', 500);
        });
    }

    /**
     * [Admin] Tạo dự án (bulk) bao gồm thông tin dự án, bảng hàng, và các lô đất.
     */
    public function bulkCreateProject(string $userId, BulkCreateProjectDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto) {
            // 1. Kiểm tra logic nếu dự án đã tồn tại (dựa vào name)
            $existingProject = $this->projectRepository->findByName($dto->project->name);
                
            $this->validate($existingProject === null, 'Dự án đã tồn tại.', 400);

            // 2. Tạo Project
            $project = $this->projectRepository->create($dto->project->toArray());

            // 3. Nếu có thông tin Area và Lots, gọi AreaService để tạo
            if ($dto->area !== null && count($dto->lots) > 0) {
                // Update Area DTO with the created project ID
                $areaData = $dto->area->toArray();
                $areaData['project_id'] = $project->id;
                $updatedAreaDto = \App\Modules\Area\DTO\CreateAreaDTO::fromArray($areaData);

                $this->areaService->createAreaWithLots($updatedAreaDto, $dto->lots);
            } else {
                // A3, A4, A6 check:
                $this->validate(false, 'Vui lòng tải lên sơ đồ bảng hàng và thêm danh sách lô đất.', 400);
            }

            return $this->success(
                $project,
                'Tạo dự án thành công.',
                201
            );
        }, useTransaction: true);
    }
}
