<?php

namespace App\Modules\Project\Http\Controllers;

use App\Core\Controller\BaseController;
use App\Modules\Project\DTO\ProjectListDTO;
use App\Modules\Project\Http\Requests\ListProjectRequest;
use App\Modules\Project\Http\Requests\SearchProjectRequest;
use App\Modules\Project\Interfaces\ProjectServiceInterface;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

final class PublicProjectController extends BaseController
{
    public function __construct(
        private readonly ProjectServiceInterface $projectService
    ) {
    }

    #[OA\Get(
        path: '/api/v1/public/projects',
        description: 'Lấy danh sách các dự án bất động sản công khai trên hệ thống.',
        summary: 'Xem danh sách dự án công khai',
        tags: ['Public Project'],
        parameters: [
            new OA\Parameter(name: 'search', description: 'Tìm kiếm theo tên dự án', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'location', description: 'Lọc theo vị trí', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'status', description: 'Lọc theo trạng thái mở bán', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'type', description: 'Lọc theo loại hình dự án', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'min_price', description: 'Giá thấp nhất', in: 'query', schema: new OA\Schema(type: 'number')),
            new OA\Parameter(name: 'max_price', description: 'Giá cao nhất', in: 'query', schema: new OA\Schema(type: 'number')),
            new OA\Parameter(name: 'per_page', description: 'Số lượng item trên mỗi trang', in: 'query', schema: new OA\Schema(type: 'integer', default: 10)),
            new OA\Parameter(name: 'page', description: 'Trang hiện tại', in: 'query', schema: new OA\Schema(type: 'integer', default: 1)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Thành công',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Tải danh sách dự án thành công.'),
                        new OA\Property(property: 'data', properties: [
                            new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Project')),
                            new OA\Property(property: 'current_page', type: 'integer'),
                            new OA\Property(property: 'last_page', type: 'integer'),
                            new OA\Property(property: 'total', type: 'integer'),
                        ], type: 'object'),
                    ]
                )
            ),
            new OA\Response(response: 500, description: 'Lỗi hệ thống')
        ]
    )]
    public function index(ListProjectRequest $request): JsonResponse
    {
        $dto = ProjectListDTO::fromRequest($request);
        $result = $this->projectService->getPublicList($dto);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Get(
        path: '/api/v1/public/projects/{id}',
        description: 'Lấy thông tin chi tiết của một dự án bất động sản công khai.',
        summary: 'Xem chi tiết dự án công khai',
        tags: ['Public Project'],
        parameters: [
            new OA\Parameter(name: 'id', description: 'ID của dự án (UUID)', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Thành công',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Tải chi tiết dự án thành công.'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/Project'),
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Dự án không tồn tại'),
            new OA\Response(response: 500, description: 'Lỗi hệ thống')
        ]
    )]
    public function show(string $id): JsonResponse
    {
        $result = $this->projectService->getPublicDetail($id);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Get(
        path: '/api/v1/public/projects/search',
        description: 'Tìm kiếm các dự án bất động sản công khai theo từ khóa (tên, vị trí, mô tả, từ khóa liên quan).',
        summary: 'Tìm kiếm dự án',
        tags: ['Public Project'],
        parameters: [
            new OA\Parameter(name: 'q', in: 'query', description: 'Từ khóa tìm kiếm', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'per_page', in: 'query', description: 'Số lượng item trên mỗi trang', schema: new OA\Schema(type: 'integer', default: 10)),
            new OA\Parameter(name: 'page', in: 'query', description: 'Trang hiện tại', schema: new OA\Schema(type: 'integer', default: 1)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Thành công',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Tìm thấy 5 dự án phù hợp.'),
                        new OA\Property(property: 'data', type: 'object', properties: [
                            new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Project')),
                            new OA\Property(property: 'current_page', type: 'integer'),
                            new OA\Property(property: 'last_page', type: 'integer'),
                            new OA\Property(property: 'total', type: 'integer'),
                        ]),
                    ]
                )
            ),
            new OA\Response(response: 422, description: 'Dữ liệu không hợp lệ (Vui lòng nhập từ khóa)'),
            new OA\Response(response: 500, description: 'Lỗi hệ thống')
        ]
    )]
    public function search(SearchProjectRequest $request): JsonResponse
    {
        $result = $this->projectService->searchProjects(
            $request->query('q'),
            (int) $request->query('per_page', 10),
            (int) $request->query('page', 1)
        );

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Get(
        path: '/api/v1/public/projects/{id}/brochure',
        description: 'Tải xuống brochure hoặc tài liệu giới thiệu của dự án.',
        summary: 'Tải brochure dự án',
        tags: ['Public Project'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', description: 'ID của dự án (UUID)', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Thành công',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Tải brochure thành công.'),
                        new OA\Property(property: 'data', type: 'object', properties: [
                            new OA\Property(property: 'url', type: 'string', example: 'https://example.com/brochure.pdf'),
                            new OA\Property(property: 'project_name', type: 'string', example: 'Vinhomes Grand Park'),
                        ]),
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Brochure đang được cập nhật hoặc Dự án không tồn tại'),
            new OA\Response(response: 500, description: 'Lỗi hệ thống')
        ]
    )]
    public function downloadBrochure(string $id): JsonResponse
    {
        $result = $this->projectService->getBrochure($id);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Get(
        path: '/api/v1/public/projects/{id}/hotline',
        description: 'Lấy số hotline tư vấn trực tiếp của dự án.',
        summary: 'Lấy hotline tư vấn',
        tags: ['Public Project'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', description: 'ID của dự án (UUID)', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Thành công',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Lấy số hotline thành công.'),
                        new OA\Property(property: 'data', type: 'object', properties: [
                            new OA\Property(property: 'hotline', type: 'string', example: '19001234'),
                        ]),
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Hotline tư vấn hiện chưa khả dụng hoặc Dự án không tồn tại'),
            new OA\Response(response: 500, description: 'Lỗi hệ thống')
        ]
    )]
    public function getHotline(string $id): JsonResponse
    {
        $result = $this->projectService->getHotline($id);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }
}
