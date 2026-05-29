<?php

namespace App\Modules\News\Http\Controllers;

use App\Core\Controller\BaseController;
use App\Modules\News\DTO\GetNewsListDTO;
use App\Modules\News\DTO\SearchNewsDTO;
use App\Modules\News\DTO\CreateCommentDTO;
use App\Modules\News\DTO\CreateInternalPostDTO;
use App\Modules\News\DTO\UpdateInternalPostDTO;
use App\Modules\News\DTO\DeleteInternalPostDTO;
use App\Modules\News\Http\Requests\SearchNewsRequest;
use App\Modules\News\Http\Requests\CreateCommentRequest;
use App\Modules\News\Http\Requests\CreateInternalPostRequest;
use App\Modules\News\Http\Requests\UpdateInternalPostRequest;
use App\Modules\News\Http\Requests\DeleteInternalPostRequest;
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
        path: '/api/v1/news/liked',
        description: 'Lấy danh sách các bài viết đã thích của người dùng hiện tại.',
        summary: 'Danh sách bài viết đã thích',
        security: [['bearerAuth' => []]],
        tags: ['News'],
        parameters: [
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
                        new OA\Property(property: 'message', type: 'string', example: 'Tải danh sách bài viết đã thích thành công.'),
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
            new OA\Response(response: 401, description: 'Chưa đăng nhập'),
            new OA\Response(response: 500, description: 'Lỗi hệ thống')
        ]
    )]
    public function getLikedNews(Request $request): JsonResponse
    {
        $userId = auth()->id();
        $result = $this->newsService->getLikedNewsList($userId, $request->all());

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

    #[OA\Get(
        path: '/api/v1/news/internal',
        description: 'Tải danh sách bài viết bảng tin nội bộ dựa trên phòng ban (Employee/Team Leader) hoặc khu vực quản lý (Director) của người dùng hiện tại.',
        summary: 'Xem bảng tin nội bộ (UC-060)',
        security: [['bearerAuth' => []]],
        tags: ['News'],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', description: 'Trang hiện tại', required: false, schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', description: 'Số lượng mỗi trang', required: false, schema: new OA\Schema(type: 'integer', default: 10)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Tải bảng tin nội bộ thành công',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Tải bảng tin nội bộ thành công.'),
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
            new OA\Response(response: 401, description: 'Chưa đăng nhập'),
            new OA\Response(response: 403, description: 'Tài khoản đã bị khóa hoặc ngừng hoạt động'),
            new OA\Response(response: 500, description: 'Không thể tải bảng tin nội bộ')
        ]
    )]
    /**
     * Xem bảng tin nội bộ (UC-060).
     * 
     * @param Request $request
     * @return JsonResponse
     * @throws \Throwable
     */
    public function getInternalFeed(Request $request): JsonResponse
    {
        $userId = auth()->id();
        $result = $this->newsService->getInternalNewsFeed($userId, $request->all());

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Get(
        path: '/api/v1/news/internal/{id}',
        description: 'Xem chi tiết bài viết nội bộ và danh sách bình luận dựa trên phòng ban (Employee/Team Leader) hoặc khu vực quản lý (Director) của người dùng hiện tại.',
        summary: 'Xem chi tiết bài viết nội bộ (UC-061)',
        security: [['bearerAuth' => []]],
        tags: ['News'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', description: 'ID của bài viết', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Tải chi tiết bài viết nội bộ thành công',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Tải chi tiết bài viết thành công.'),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(
                                    property: 'detail',
                                    type: 'object',
                                    properties: [
                                        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
                                        new OA\Property(property: 'title', type: 'string'),
                                        new OA\Property(property: 'slug', type: 'string'),
                                        new OA\Property(property: 'summary', type: 'string', nullable: true),
                                        new OA\Property(property: 'content', type: 'string'),
                                        new OA\Property(property: 'thumbnail', type: 'string', nullable: true),
                                        new OA\Property(property: 'category', type: 'string'),
                                        new OA\Property(property: 'department', type: 'string', nullable: true),
                                        new OA\Property(property: 'area', type: 'string', nullable: true),
                                        new OA\Property(property: 'likes_count', type: 'integer'),
                                        new OA\Property(property: 'comments_count', type: 'integer'),
                                        new OA\Property(property: 'is_liked', type: 'boolean'),
                                        new OA\Property(property: 'published_at', type: 'string', format: 'date-time'),
                                        new OA\Property(
                                            property: 'author',
                                            type: 'object',
                                            properties: [
                                                new OA\Property(property: 'name', type: 'string'),
                                                new OA\Property(property: 'avatar', type: 'string', nullable: true)
                                            ]
                                        ),
                                        new OA\Property(
                                            property: 'attachments',
                                            type: 'array',
                                            items: new OA\Items(
                                                type: 'object',
                                                properties: [
                                                    new OA\Property(property: 'type', type: 'string'),
                                                    new OA\Property(property: 'url', type: 'string'),
                                                    new OA\Property(property: 'name', type: 'string')
                                                ]
                                            )
                                        )
                                    ]
                                ),
                                new OA\Property(
                                    property: 'comments',
                                    type: 'array',
                                    items: new OA\Items(
                                        type: 'object',
                                        properties: [
                                            new OA\Property(property: 'id', type: 'string', format: 'uuid'),
                                            new OA\Property(property: 'user_id', type: 'string', format: 'uuid'),
                                            new OA\Property(property: 'user_name', type: 'string'),
                                            new OA\Property(property: 'user_avatar', type: 'string', nullable: true),
                                            new OA\Property(property: 'content', type: 'string'),
                                            new OA\Property(property: 'created_at', type: 'string', format: 'date-time')
                                        ]
                                    )
                                )
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Chưa đăng nhập'),
            new OA\Response(response: 403, description: 'Bạn không có quyền truy cập bài viết này'),
            new OA\Response(response: 404, description: 'Bài viết không tồn tại'),
            new OA\Response(response: 500, description: 'Lỗi tải chi tiết bài viết')
        ]
    )]
    public function getInternalDetail(string $id): JsonResponse
    {
        $userId = auth()->id();
        $result = $this->newsService->getInternalNewsDetail($id, $userId);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Post(
        path: '/api/v1/news/internal/{id}/comments',
        description: 'Gửi bình luận mới cho bài viết nội bộ dựa trên phân quyền.',
        summary: 'Bình luận bài viết nội bộ (UC-064)',
        security: [['bearerAuth' => []]],
        tags: ['News'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', description: 'ID của bài viết', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['content'],
                properties: [
                    new OA\Property(property: 'content', type: 'string', example: 'Đây là bình luận mẫu của tôi.')
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Gửi bình luận thành công',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Đã gửi bình luận thành công.'),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'string', format: 'uuid'),
                                new OA\Property(property: 'news_id', type: 'string', format: 'uuid'),
                                new OA\Property(property: 'user_id', type: 'string', format: 'uuid'),
                                new OA\Property(property: 'user_name', type: 'string'),
                                new OA\Property(property: 'user_avatar', type: 'string', nullable: true),
                                new OA\Property(property: 'content', type: 'string'),
                                new OA\Property(property: 'created_at', type: 'string', format: 'date-time')
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Chưa đăng nhập'),
            new OA\Response(response: 403, description: 'Bạn không có quyền truy cập bài viết này'),
            new OA\Response(response: 404, description: 'Bài viết không tồn tại'),
            new OA\Response(response: 422, description: 'Dữ liệu không hợp lệ')
        ]
    )]
    public function addComment(CreateCommentRequest $request, string $id): JsonResponse
    {
        $userId = auth()->id();
        $dto = CreateCommentDTO::fromRequest($request, $id, $userId);
        $result = $this->newsService->createComment($dto);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Post(
        path: '/api/v1/news/internal',
        description: 'Tạo bài viết nội bộ mới trong phạm vi phòng ban (Employee/Team Leader) hoặc khu vực quản lý (Director) của người dùng hiện tại.',
        summary: 'Đăng bài viết nội bộ (UC-062)',
        security: [['bearerAuth' => []]],
        tags: ['News'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['content'],
                    properties: [
                        new OA\Property(property: 'title', type: 'string', description: 'Tiêu đề bài viết (không bắt buộc)', nullable: true),
                        new OA\Property(property: 'content', type: 'string', description: 'Nội dung bài viết'),
                        new OA\Property(property: 'thumbnail', type: 'string', format: 'binary', description: 'Ảnh đại diện/ảnh đính kèm bài viết (jpeg, png, jpg, gif, svg, tối đa 2MB)', nullable: true)
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Đăng bài viết thành công.',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Đăng bài viết thành công.'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/News')
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Chưa đăng nhập'),
            new OA\Response(response: 403, description: 'Bạn không có quyền đăng bài viết nội bộ hoặc tài khoản bị khóa'),
            new OA\Response(response: 422, description: 'Vui lòng nhập nội dung bài viết hoặc file hình ảnh không hợp lệ'),
            new OA\Response(response: 500, description: 'Không thể đăng bài viết')
        ]
    )]
    public function createInternal(CreateInternalPostRequest $request): JsonResponse
    {
        $userId = auth()->id();
        $dto = CreateInternalPostDTO::fromRequest($request, $userId);
        $result = $this->newsService->createInternalPost($dto);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Post(
        path: '/api/v1/news/internal/{id}',
        description: 'Chỉnh sửa bài viết nội bộ do chính mình tạo.',
        summary: 'Chỉnh sửa bài viết nội bộ (UC-065)',
        security: [['bearerAuth' => []]],
        tags: ['News'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', description: 'ID của bài viết nội bộ', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['content'],
                    properties: [
                        new OA\Property(property: 'title', type: 'string', description: 'Tiêu đề bài viết (không bắt buộc)', nullable: true),
                        new OA\Property(property: 'content', type: 'string', description: 'Nội dung bài viết'),
                        new OA\Property(property: 'thumbnail', type: 'string', format: 'binary', description: 'Ảnh đại diện/ảnh đính kèm bài viết (jpeg, png, jpg, gif, svg, tối đa 2MB)', nullable: true),
                        new OA\Property(property: '_method', type: 'string', description: 'Giả lập HTTP method (PUT/PATCH) khi sử dụng multipart/form-data', default: 'PUT', nullable: true)
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Cập nhật bài viết thành công.',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Cập nhật bài viết thành công.'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/News')
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Chưa đăng nhập'),
            new OA\Response(response: 403, description: 'Bạn không có quyền chỉnh sửa bài viết này'),
            new OA\Response(response: 404, description: 'Bài viết không tồn tại'),
            new OA\Response(response: 422, description: 'Vui lòng nhập nội dung bài viết'),
            new OA\Response(response: 500, description: 'Không thể cập nhật bài viết')
        ]
    )]
    public function updateInternal(UpdateInternalPostRequest $request, string $id): JsonResponse
    {
        $userId = auth()->id();
        $dto = UpdateInternalPostDTO::fromRequest($request, $id, $userId);
        $result = $this->newsService->updateInternalPost($dto);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Delete(
        path: '/api/v1/news/internal/{id}',
        description: 'Cho phép người dùng xóa bài viết nội bộ do chính mình tạo.',
        summary: 'Xóa bài viết nội bộ (UC-066)',
        security: [['bearerAuth' => []]],
        tags: ['News'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                description: 'ID của bài viết nội bộ',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Xóa bài viết thành công.',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Xóa bài viết thành công.'),
                        new OA\Property(property: 'data', type: 'object', nullable: true)
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Chưa đăng nhập'),
            new OA\Response(response: 403, description: 'Bạn không có quyền xóa bài viết này'),
            new OA\Response(response: 404, description: 'Bài viết không tồn tại'),
            new OA\Response(response: 500, description: 'Không thể xóa bài viết')
        ]
    )]
    public function deleteInternal(DeleteInternalPostRequest $request, string $id): JsonResponse
    {
        $userId = auth()->id();
        $dto = DeleteInternalPostDTO::fromRequest($request, $id, $userId);
        $result = $this->newsService->deleteInternalPost($dto);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Post(
        path: '/api/v1/news/internal/{id}/like',
        description: 'Cho phép người dùng đã đăng nhập nhấn thích hoặc bỏ thích bài viết nội bộ dựa trên phân quyền.',
        summary: 'Thích/Bỏ thích bài viết nội bộ (UC-063)',
        security: [['bearerAuth' => []]],
        tags: ['News'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', description: 'ID bài viết nội bộ', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
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
            new OA\Response(response: 401, description: 'Chưa đăng nhập'),
            new OA\Response(response: 403, description: 'Bạn không có quyền tương tác bài viết này'),
            new OA\Response(response: 404, description: 'Bài viết không tồn tại'),
            new OA\Response(response: 500, description: 'Không thể cập nhật lượt thích. Vui lòng thử lại.')
        ]
    )]
    public function likeInternal(string $id): JsonResponse
    {
        $userId = auth()->id();
        $result = $this->newsService->likeInternalPost($id, $userId);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }
}
