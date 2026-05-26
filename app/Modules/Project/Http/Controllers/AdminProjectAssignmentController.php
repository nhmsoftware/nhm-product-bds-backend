<?php

declare(strict_types=1);

namespace App\Modules\Project\Http\Controllers;

use App\Core\Controller\BaseController;
use App\Modules\Project\DTO\AssignPermissionDTO;
use App\Modules\Project\Http\Requests\AssignProjectPermissionRequest;
use App\Modules\Project\Interfaces\ProjectAssignmentServiceInterface;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Admin Project Assignments', description: 'API quản lý phân quyền truy cập dự án (UC-090)')]
class AdminProjectAssignmentController extends BaseController
{
    public function __construct(
        private readonly ProjectAssignmentServiceInterface $assignmentService
    ) {}

    #[OA\Post(
        path: '/api/v1/admin/projects/{project}/assignments',
        summary: 'Phân quyền truy cập inventory (UC-090)',
        description: 'Cấp quyền truy cập dự án/bảng hàng cho nhân viên hoặc phòng ban.',
        security: [['bearerAuth' => []]],
        tags: ['Admin Project Assignments'],
        parameters: [
            new OA\Parameter(name: 'project', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'assignable_id', type: 'string', format: 'uuid'),
                    new OA\Property(property: 'assignable_type', type: 'string', enum: ['user', 'department']),
                    new OA\Property(property: 'permissions', type: 'array', items: new OA\Items(type: 'string'))
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Tạo mới hoặc cập nhật thành công')
        ]
    )]
    public function store(AssignProjectPermissionRequest $request, string $project): JsonResponse
    {
        $userId = (string) $request->user()->id;
        $dto = AssignPermissionDTO::fromRequest($request);

        $result = $this->assignmentService->assignPermission($userId, $project, $dto);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage(), $result->getCode() ?: 201);
    }
}
