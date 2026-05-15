<?php

namespace App\Modules\Project\Services;

use App\Core\Services\ServiceReturn;
use App\Core\Services\BaseService;
use App\Modules\Project\DTO\ProjectListDTO;
use App\Modules\Project\Interfaces\ProjectRepositoryInterface;
use App\Modules\Project\Interfaces\ProjectServiceInterface;

/**
 * Class ProjectService
 * 
 * @package App\Modules\Project\Services
 */
final class ProjectService extends BaseService implements ProjectServiceInterface
{
    /**
     * @param ProjectRepositoryInterface $projectRepository
     */
    public function __construct(
        private readonly ProjectRepositoryInterface $projectRepository
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
                $project,
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

            $message = $projects->total() > 0 
                ? 'Tìm thấy ' . $projects->total() . ' dự án phù hợp.' 
                : 'Không tìm thấy dự án phù hợp.';

            return $this->success(
                $projects,
                $message
            );
        }, useTransaction: false);
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
}
