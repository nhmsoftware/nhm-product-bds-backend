<?php

declare(strict_types=1);

namespace App\Modules\News\Http\Controllers;

use App\Core\Controller\BaseController;
use App\Modules\News\Interfaces\AdminNewsServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

final class AdminNewsController extends BaseController
{
    public function __construct(
        private readonly AdminNewsServiceInterface $adminNewsService
    ) {
    }

    #[OA\Get(
        path: '/api/v1/admin/news',
        description: 'Lấy danh sách tin tức dành cho Super Admin quản lý.',
        summary: 'Danh sách tin tức (UC-122)',
        security: [['bearerAuth' => []]],
        tags: ['Admin - News'],
        parameters: [
            new OA\Parameter(name: 'search', in: 'query', description: 'Tìm kiếm theo tiêu đề', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'is_published', in: 'query', description: 'Trạng thái hiển thị (true/false)', required: false, schema: new OA\Schema(type: 'boolean')),
            new OA\Parameter(name: 'type', in: 'query', description: 'Loại bài viết (public/internal)', required: false, schema: new OA\Schema(type: 'string', enum: ['public', 'internal'])),
            new OA\Parameter(name: 'page', in: 'query', description: 'Trang hiện tại', required: false, schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', description: 'Số lượng / trang', required: false, schema: new OA\Schema(type: 'integer', default: 15))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Tải danh sách tin tức thành công'),
            new OA\Response(response: 403, description: 'Không có quyền truy cập')
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $dto = \App\Modules\News\DTO\AdminListNewsDTO::fromRequest($request);
        $result = $this->adminNewsService->getList($dto);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Get(
        path: '/api/v1/admin/news/{id}',
        description: 'Xem chi tiết một bài viết tin tức (UC-122).',
        summary: 'Chi tiết tin tức',
        security: [['bearerAuth' => []]],
        tags: ['Admin - News'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Lấy thông tin bài viết thành công'),
            new OA\Response(response: 404, description: 'Bài viết không tồn tại')
        ]
    )]
    public function show(string $id): JsonResponse
    {
        $result = $this->adminNewsService->getDetail($id);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Post(
        path: '/api/v1/admin/news',
        description: 'Tạo bài viết tin tức mới (UC-123).',
        summary: 'Tạo tin tức (UC-123)',
        security: [['bearerAuth' => []]],
        tags: ['Admin - News'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['title', 'category', 'type', 'status'],
                properties: [
                    new OA\Property(property: 'title', type: 'string'),
                    new OA\Property(property: 'summary', type: 'string', nullable: true),
                    new OA\Property(property: 'content', type: 'string', description: 'Nội dung plain text, có thể bỏ trống nếu gửi content_blocks.'),
                    new OA\Property(property: 'content_blocks', type: 'array', nullable: true, items: new OA\Items(type: 'object'), description: 'Mảng block nội dung: heading, paragraph, image, quote.'),
                    new OA\Property(property: 'thumbnail', type: 'string', nullable: true),
                    new OA\Property(property: 'category', type: 'string'),
                    new OA\Property(property: 'type', type: 'string', enum: ['public', 'internal'], description: 'Loại bài viết: Công khai hoặc Nội bộ'),
                    new OA\Property(property: 'scope', type: 'string', enum: ['company', 'department'], description: 'Phạm vi hiển thị (chỉ dùng khi type=internal)'),
                    new OA\Property(property: 'department', type: 'string', nullable: true, description: 'Phòng ban (chỉ dùng khi scope=department)'),
                    new OA\Property(property: 'status', type: 'string', enum: ['published', 'hidden'], description: 'Trạng thái bài viết'),
                    new OA\Property(property: 'is_featured', type: 'boolean', default: false)
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Tạo tin tức thành công')
        ]
    )]
    public function store(\App\Modules\News\Http\Requests\Admin\CreateNewsRequest $request): JsonResponse
    {

        $dto = \App\Modules\News\DTO\AdminCreateNewsDTO::fromRequest($request);
        $result = $this->adminNewsService->create($dto);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Put(
        path: '/api/v1/admin/news/{id}',
        description: 'Cập nhật bài viết tin tức (UC-124).',
        summary: 'Cập nhật tin tức (UC-124)',
        security: [['bearerAuth' => []]],
        tags: ['Admin - News'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'title', type: 'string'),
                    new OA\Property(property: 'summary', type: 'string', nullable: true),
                    new OA\Property(property: 'content', type: 'string'),
                    new OA\Property(property: 'content_blocks', type: 'array', nullable: true, items: new OA\Items(type: 'object'), description: 'Mảng block nội dung: heading, paragraph, image, quote.'),
                    new OA\Property(property: 'thumbnail', type: 'string', nullable: true),
                    new OA\Property(property: 'category', type: 'string'),
                    new OA\Property(property: 'type', type: 'string', enum: ['public', 'internal'], description: 'Loại bài viết'),
                    new OA\Property(property: 'scope', type: 'string', enum: ['company', 'department'], description: 'Phạm vi hiển thị (chỉ dùng khi type=internal)'),
                    new OA\Property(property: 'department', type: 'string', nullable: true, description: 'Phòng ban (chỉ dùng khi scope=department)'),
                    new OA\Property(property: 'status', type: 'string', enum: ['published', 'hidden'], description: 'Trạng thái bài viết'),
                    new OA\Property(property: 'is_featured', type: 'boolean')
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Cập nhật tin tức thành công'),
            new OA\Response(response: 404, description: 'Bài viết không tồn tại')
        ]
    )]
    public function update(\App\Modules\News\Http\Requests\Admin\UpdateNewsRequest $request, string $id): JsonResponse
    {
        $dto = \App\Modules\News\DTO\AdminUpdateNewsDTO::fromRequest($request, $id);
        $result = $this->adminNewsService->update($dto);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Delete(
        path: '/api/v1/admin/news/{id}',
        description: 'Ẩn bài viết tin tức (UC-125).',
        summary: 'Ẩn tin tức (UC-125)',
        security: [['bearerAuth' => []]],
        tags: ['Admin - News'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Ẩn bài viết thành công'),
            new OA\Response(response: 404, description: 'Bài viết không tồn tại')
        ]
    )]
    public function destroy(string $id): JsonResponse
    {
        $result = $this->adminNewsService->delete($id);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }
}
