<?php

namespace App\Modules\Dashboard\Http\Controllers;

use App\Core\Controller\BaseController;
use App\Modules\Dashboard\DTO\GetCommentsDTO;
use App\Modules\Dashboard\Http\Requests\DeleteCommentRequest;
use App\Modules\Dashboard\Interfaces\AdminCommentServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

final class AdminCommentController extends BaseController
{
    public function __construct(
        private readonly AdminCommentServiceInterface $adminCommentService
    ) {}

    #[OA\Get(
        path: '/api/dashboard/admin/comments',
        summary: 'Lấy danh sách tất cả bình luận (UC-095)',
        tags: ['Dashboard', 'Manage Comment'],
        parameters: [
            new OA\Parameter(name: 'keyword', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'type', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['lot_internal', 'news_public', 'news_internal'])),
            new OA\Parameter(name: 'project_id', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'area_id', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 15)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Thành công'),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $dto = GetCommentsDTO::fromRequest($request);
        $result = $this->adminCommentService->getList(auth()->id(), $dto);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Delete(
        path: '/api/dashboard/admin/comments/{id}',
        summary: 'Xóa bình luận (UC-095)',
        tags: ['Dashboard', 'Manage Comment'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'type', in: 'query', required: true, schema: new OA\Schema(type: 'string', enum: ['lot_internal', 'news_public', 'news_internal'])),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Xóa thành công'),
        ]
    )]
    public function destroy(DeleteCommentRequest $request, string $id): JsonResponse
    {
        $sourceType = $request->query('type');
        $result = $this->adminCommentService->deleteComment(auth()->id(), $id, $sourceType);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess(null, $result->getMessage());
    }
}
