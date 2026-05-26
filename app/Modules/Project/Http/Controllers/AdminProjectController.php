<?php

declare(strict_types=1);

namespace App\Modules\Project\Http\Controllers;

use App\Core\Controller\BaseController;
use App\Modules\Project\Http\Requests\CreateProjectRequest;
use App\Modules\Project\Http\Requests\UpdateProjectRequest;
use App\Modules\Project\Http\Requests\ListAdminProjectRequest;
use App\Modules\Project\DTO\CreateProjectDTO;
use App\Modules\Project\DTO\UpdateProjectDTO;
use App\Modules\Project\DTO\ListAdminProjectDTO;
use App\Modules\Project\DTO\BulkCreateProjectDTO;
use App\Modules\Project\Http\Requests\BulkCreateProjectRequest;
use App\Modules\Project\Interfaces\ProjectServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Admin Project', description: 'API quản lý dự án bất động sản (Admin)')]
class AdminProjectController extends BaseController
{
    public function __construct(
        private readonly ProjectServiceInterface $projectService
    ) {}

    #[OA\Get(
        path: '/api/v1/admin/projects',
        summary: 'Xem danh sách dự án (UC-085)',
        description: 'Super Admin, General Director xem danh sách dự án. General Director chỉ xem dự án của chi nhánh mình.',
        security: [['bearerAuth' => []]],
        tags: ['Admin Project'],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 10)),
            new OA\Parameter(name: 'keyword', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'sort_by', in: 'query', required: false, schema: new OA\Schema(type: 'string', default: 'created_at')),
            new OA\Parameter(name: 'direction', in: 'query', required: false, schema: new OA\Schema(type: 'string', default: 'desc')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Danh sách dự án thành công')
        ]
    )]
    public function index(ListAdminProjectRequest $request): JsonResponse
    {
        $userId = (string) $request->user()->id;
        $dto = ListAdminProjectDTO::fromRequest($request);

        $result = $this->projectService->getAdminProjects($userId, $dto);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Get(
        path: '/api/v1/admin/projects/{id}',
        summary: 'Xem chi tiết dự án và sơ đồ bảng hàng (UC-085)',
        description: 'Xem chi tiết dự án kèm theo các phân khu/bảng hàng và lô đất.',
        security: [['bearerAuth' => []]],
        tags: ['Admin Project'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Chi tiết dự án thành công')
        ]
    )]
    public function show(Request $request, string $id): JsonResponse
    {
        $userId = (string) $request->user()->id;
        $result = $this->projectService->getProjectDetailAdmin($userId, $id);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Post(
        path: '/api/v1/admin/projects',
        summary: 'Tạo dự án mới (UC-085)',
        security: [['bearerAuth' => []]],
        tags: ['Admin Project'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/Project')
        ),
        responses: [
            new OA\Response(response: 201, description: 'Tạo dự án thành công')
        ]
    )]
    public function store(CreateProjectRequest $request): JsonResponse
    {
        $userId = (string) $request->user()->id;
        $dto = CreateProjectDTO::fromRequest($request);

        $result = $this->projectService->createProject($userId, $dto);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage(), 201);
    }

    #[OA\Post(
        path: '/api/v1/admin/projects/bulk-create',
        summary: 'Hoàn tất tạo dự án (Bulk Create UC-086)',
        description: 'Tạo dự án kèm theo khu đất (bảng hàng) và danh sách lô đất trong một transaction duy nhất.',
        security: [['bearerAuth' => []]],
        tags: ['Admin Project'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string'),
                    new OA\Property(property: 'location', type: 'string'),
                    new OA\Property(property: 'price', type: 'integer'),
                    new OA\Property(property: 'status', type: 'integer'),
                    new OA\Property(property: 'type', type: 'string'),
                    new OA\Property(property: 'area', type: 'object'),
                    new OA\Property(property: 'lots', type: 'array', items: new OA\Items(type: 'object'))
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Tạo dự án và lô đất hàng loạt thành công')
        ]
    )]
    public function bulkCreate(BulkCreateProjectRequest $request): JsonResponse
    {
        $userId = (string) $request->user()->id;
        $dto = BulkCreateProjectDTO::fromRequest($request);

        $result = $this->projectService->bulkCreateProject($userId, $dto);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage(), 201);
    }

    #[OA\Put(
        path: '/api/v1/admin/projects/{id}',
        summary: 'Cập nhật dự án (UC-087)',
        security: [['bearerAuth' => []]],
        tags: ['Admin Project'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/Project')
        ),
        responses: [
            new OA\Response(response: 200, description: 'Cập nhật dự án thành công')
        ]
    )]
    public function update(UpdateProjectRequest $request, string $id): JsonResponse
    {
        $userId = (string) $request->user()->id;
        $dto = UpdateProjectDTO::fromRequest($request);

        $result = $this->projectService->updateProject($userId, $id, $dto);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Patch(
        path: '/api/v1/admin/projects/{id}/lock',
        summary: 'Khóa/Mở khóa dự án (UC-088)',
        security: [['bearerAuth' => []]],
        tags: ['Admin Project'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'is_locked', type: 'boolean')
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Khóa/Mở khóa dự án thành công')
        ]
    )]
    public function lock(Request $request, string $id): JsonResponse
    {
        $userId = (string) $request->user()->id;
        $isLocked = $request->boolean('is_locked', true);

        $result = $this->projectService->lockUnlockProject($userId, $id, $isLocked);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }
}
