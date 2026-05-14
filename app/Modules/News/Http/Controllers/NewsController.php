<?php

namespace App\Modules\News\Http\Controllers;

use App\Core\Controller\BaseController;
use App\Modules\News\DTO\GetNewsListDTO;
use App\Modules\News\DTO\SearchNewsDTO;
use App\Modules\News\Http\Requests\SearchNewsRequest;
use App\Modules\News\Interfaces\NewsServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

final class NewsController extends BaseController
{
    public function __construct(
        private readonly NewsServiceInterface $newsService
    ) {
    }

    #[OA\Get(
        path: '/api/v1/news',
        description: 'Lấy danh sách tin tức công khai, bao gồm tin nổi bật và phân trang.',
        summary: 'Xem danh sách tin tức (UC-08)',
        tags: ['News'],
        parameters: [
            new OA\Parameter(name: 'category', in: 'query', description: 'Lọc theo danh mục', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'search', in: 'query', description: 'Tìm kiếm theo tiêu đề hoặc tóm tắt', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'page', in: 'query', description: 'Trang hiện tại', required: false, schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', description: 'Số lượng mỗi trang', required: false, schema: new OA\Schema(type: 'integer', default: 10)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Tải dữ liệu thành công',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Tải danh sách tin tức thành công.'),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'featured', type: 'array', items: new OA\Items(ref: '#/components/schemas/News')),
                                new OA\Property(property: 'list', type: 'array', items: new OA\Items(ref: '#/components/schemas/News')),
                                new OA\Property(
                                    property: 'pagination',
                                    type: 'object',
                                    properties: [
                                        new OA\Property(property: 'total', type: 'integer'),
                                        new OA\Property(property: 'per_page', type: 'integer'),
                                        new OA\Property(property: 'current_page', type: 'integer'),
                                        new OA\Property(property: 'last_page', type: 'integer'),
                                    ]
                                ),
                                new OA\Property(property: 'categories', type: 'array', items: new OA\Items(type: 'object'))
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(response: 500, description: 'Lỗi hệ thống')
        ]
    )]
    /**
     * Xem danh sách tin tức công khai (UC-08).
     * 
     * @param Request $request
     * @return JsonResponse
     * @throws \Throwable
     */
    public function index(Request $request): JsonResponse
    {
        $dto = GetNewsListDTO::fromRequest($request);
        $result = $this->newsService->getList($dto);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Get(
        path: '/api/v1/news/search',
        description: 'Tìm kiếm bài viết tin tức theo từ khóa (tiêu đề, tóm tắt, nội dung).',
        summary: 'Tìm kiếm tin tức công khai (UC-09)',
        tags: ['News'],
        parameters: [
            new OA\Parameter(name: 'keyword', in: 'query', description: 'Từ khóa tìm kiếm', required: true, schema: new OA\Schema(type: 'string', minLength: 1)),
            new OA\Parameter(name: 'page', in: 'query', description: 'Trang hiện tại', required: false, schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', description: 'Số lượng mỗi trang', required: false, schema: new OA\Schema(type: 'integer', default: 10)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Tìm kiếm thành công',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Tìm kiếm tin tức thành công.'),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'list', type: 'array', items: new OA\Items(ref: '#/components/schemas/News')),
                                new OA\Property(
                                    property: 'pagination',
                                    type: 'object',
                                    properties: [
                                        new OA\Property(property: 'total', type: 'integer'),
                                        new OA\Property(property: 'per_page', type: 'integer'),
                                        new OA\Property(property: 'current_page', type: 'integer'),
                                        new OA\Property(property: 'last_page', type: 'integer'),
                                    ]
                                ),
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(response: 422, description: 'Vui lòng nhập từ khóa tìm kiếm'),
            new OA\Response(response: 500, description: 'Lỗi hệ thống')
        ]
    )]
    /**
     * Tìm kiếm tin tức công khai (UC-09).
     * 
     * @param SearchNewsRequest $request
     * @return JsonResponse
     * @throws \Throwable
     */
    public function search(SearchNewsRequest $request): JsonResponse
    {
        $dto = SearchNewsDTO::fromRequest($request);
        $result = $this->newsService->search($dto);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Get(
        path: '/api/v1/news/{idOrSlug}',
        description: 'Lấy thông tin chi tiết của một bài viết theo ID hoặc Slug, kèm theo các bài viết liên quan.',
        summary: 'Xem chi tiết bài viết (UC-11)',
        tags: ['News'],
        parameters: [
            new OA\Parameter(name: 'idOrSlug', in: 'path', description: 'ID hoặc Slug của bài viết', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Tải dữ liệu thành công',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Tải chi tiết bài viết thành công.'),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'detail', ref: '#/components/schemas/News'),
                                new OA\Property(property: 'related', type: 'array', items: new OA\Items(ref: '#/components/schemas/News')),
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(response: 403, description: 'Bạn không có quyền truy cập bài viết này'),
            new OA\Response(response: 404, description: 'Bài viết không tồn tại hoặc đã bị xóa'),
            new OA\Response(response: 500, description: 'Lỗi hệ thống')
        ]
    )]
    /**
     * Xem chi tiết bài viết (UC-11).
     * 
     * @param string $idOrSlug
     * @return JsonResponse
     * @throws \Throwable
     */
    public function show(string $idOrSlug): JsonResponse
    {
        $result = $this->newsService->getDetail($idOrSlug);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Post(
        path: '/api/v1/news/{id}/like',
        description: 'Cho phép người dùng đã đăng nhập nhấn thích hoặc bỏ thích bài viết.',
        summary: 'Thích/Bỏ thích bài viết (UC-12)',
        security: [['bearerAuth' => []]],
        tags: ['News'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', description: 'ID bài viết', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Thao tác thành công',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Đã thích bài viết.'),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'liked', type: 'boolean', example: true),
                                new OA\Property(property: 'likes_count', type: 'integer', example: 10),
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Vui lòng đăng nhập để thực hiện chức năng này'),
            new OA\Response(response: 404, description: 'Bài viết không tồn tại'),
            new OA\Response(response: 500, description: 'Lỗi hệ thống')
        ]
    )]
    /**
     * Thích/Bỏ thích bài viết (UC-12).
     * 
     * @param string $id
     * @return JsonResponse
     * @throws \Throwable
     */
    public function like(string $id): JsonResponse
    {
        $userId = auth()->id();
        $result = $this->newsService->like($id, $userId);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }
}
