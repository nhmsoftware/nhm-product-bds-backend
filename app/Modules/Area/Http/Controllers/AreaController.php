<?php

declare(strict_types=1);

namespace App\Modules\Area\Http\Controllers;

use App\Core\Controller\BaseController;
use App\Modules\Area\Http\Requests\ListAreaRequest;
use App\Modules\Area\Http\Requests\SearchInventoryRequest;
use App\Modules\Area\Http\Requests\CreateLotCommentRequest;
use App\Modules\Area\Http\Requests\RequestLockLotRequest;
use App\Modules\Area\DTO\SearchInventoryDTO;
use App\Modules\Area\DTO\CreateLotCommentDTO;
use App\Modules\Area\DTO\RequestLockLotDTO;
use App\Modules\Area\Interfaces\AreaServiceInterface;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Area', description: 'API for managing and viewing land areas / sales boards')]
class AreaController extends BaseController
{
    /**
     * Khởi tạo Controller và inject service.
     *
     * @param AreaServiceInterface $areaService
     */
    public function __construct(
        private readonly AreaServiceInterface $areaService
    ) {}

    #[OA\Get(
        path: '/api/v1/areas',
        description: 'Cho phép Employee, Team Leader, Director, General Director xem danh sách các khu đất/bảng hàng họ được phân quyền.',
        summary: 'Xem danh sách khu đất/bảng hàng được cấp quyền (UC-077)',
        security: [['bearerAuth' => []]],
        tags: ['Area'],
        parameters: [
            new OA\Parameter(
                name: 'page',
                description: 'Trang hiện tại (bắt đầu từ 1)',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 1)
            ),
            new OA\Parameter(
                name: 'per_page',
                description: 'Số bản ghi trên mỗi trang',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 10)
            ),
            new OA\Parameter(
                name: 'sort_by',
                description: 'Trường dùng để sắp xếp',
                in: 'query',
                required: false,
                schema: new OA\Schema(
                    type: 'string',
                    enum: ['id', 'created_at', 'name', 'total_lots', 'remaining_lots'],
                    default: 'created_at'
                )
            ),
            new OA\Parameter(
                name: 'direction',
                in: 'query',
                required: false,
                description: 'Chiều sắp xếp',
                schema: new OA\Schema(type: 'string', enum: ['asc', 'desc'], default: 'desc')
            ),
            new OA\Parameter(
                name: 'filters[is_featured]',
                in: 'query',
                required: false,
                description: 'Lọc theo trạng thái nổi bật (true/false)',
                schema: new OA\Schema(type: 'string', enum: ['true', 'false'])
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Tải danh sách khu đất thành công hoặc hiển thị danh sách trống/không có quyền.',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Tải danh sách khu đất thành công.'),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'current_page', type: 'integer', example: 1),
                                new OA\Property(
                                    property: 'data',
                                    type: 'array',
                                    items: new OA\Items(ref: '#/components/schemas/Area')
                                ),
                                new OA\Property(property: 'first_page_url', type: 'string', example: 'http://localhost/api/v1/areas?page=1'),
                                new OA\Property(property: 'from', type: 'integer', example: 1),
                                new OA\Property(property: 'last_page', type: 'integer', example: 1),
                                new OA\Property(property: 'last_page_url', type: 'string', example: 'http://localhost/api/v1/areas?page=1'),
                                new OA\Property(property: 'next_page_url', type: 'string', nullable: true, example: null),
                                new OA\Property(property: 'path', type: 'string', example: 'http://localhost/api/v1/areas'),
                                new OA\Property(property: 'per_page', type: 'integer', example: 10),
                                new OA\Property(property: 'prev_page_url', type: 'string', nullable: true, example: null),
                                new OA\Property(property: 'to', type: 'integer', example: 2),
                                new OA\Property(property: 'total', type: 'integer', example: 2)
                            ],
                            type: 'object'
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Chưa đăng nhập (Unauthenticated)'
            ),
            new OA\Response(
                response: 403,
                description: 'Không có quyền truy cập bảng hàng hoặc tài khoản bị khóa.',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Bạn không có quyền xem bảng hàng.')
                    ]
                )
            ),
            new OA\Response(
                response: 500,
                description: 'Không thể tải danh sách do lỗi hệ thống hoặc kết nối cơ sở dữ liệu.',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Không thể tải danh sách khu đất. Vui lòng thử lại.')
                    ]
                )
            )
        ]
    )]
    public function index(ListAreaRequest $request): JsonResponse
    {
        $userId = (string) $request->user()->id;
        $filter = $request->getFilterOptions();

        $result = $this->areaService->getAssignedLandAreas($userId, $filter);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Get(
        path: '/api/v1/areas/{id}/inventory-map',
        summary: 'Xem sơ đồ bảng hàng của khu đất theo trạng thái từng lô (UC-078)',
        description: 'Cho phép người dùng xem sơ đồ bảng hàng của một khu đất cụ thể mà họ có quyền truy cập.',
        security: [['bearerAuth' => []]],
        tags: ['Area'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'ID của khu đất',
                schema: new OA\Schema(type: 'string', format: 'uuid')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Tải sơ đồ bảng hàng thành công.',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Tải sơ đồ bảng hàng thành công.'),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'area_id', type: 'string', format: 'uuid', example: 'd3b07384-d113-4ec2-a5d6-c734b1234567'),
                                new OA\Property(property: 'area_name', type: 'string', example: 'Phân khu A'),
                                new OA\Property(property: 'sales_board_image', type: 'string', example: 'https://example.com/board.jpg'),
                                new OA\Property(
                                    property: 'lots',
                                    type: 'array',
                                    items: new OA\Items(ref: '#/components/schemas/Lot')
                                )
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Chưa đăng nhập (Unauthenticated)'
            ),
            new OA\Response(
                response: 403,
                description: 'Không có quyền xem bảng hàng hoặc không có quyền truy cập khu đất này.',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Bạn không có quyền truy cập khu đất này.')
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Khu đất không tồn tại hoặc chưa có dữ liệu bảng hàng.',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Chưa có dữ liệu bảng hàng.')
                    ]
                )
            ),
            new OA\Response(
                response: 500,
                description: 'Lỗi hệ thống khi tải sơ đồ bảng hàng.'
            )
        ]
    )]
    public function inventoryMap(string $id, \Illuminate\Http\Request $request): JsonResponse
    {
        $userId = (string) $request->user()->id;
        $result = $this->areaService->getInventoryMap($userId, $id);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Get(
        path: '/api/v1/lots/{id}',
        summary: 'Xem chi tiết lô đất (UC-080)',
        description: 'Tải thông tin chi tiết của một lô đất bao gồm quy hoạch và bình luận nội bộ.',
        security: [['bearerAuth' => []]],
        tags: ['Area'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'ID của lô đất',
                schema: new OA\Schema(type: 'string', format: 'uuid')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Tải chi tiết lô đất thành công.',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Tải chi tiết lô đất thành công.'),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'string', format: 'uuid', example: 'd3b07384-d113-4ec2-a5d6-c734b1234567'),
                                new OA\Property(property: 'area_id', type: 'string', format: 'uuid', example: 'd3b07384-d113-4ec2-a5d6-c734b2234567'),
                                new OA\Property(property: 'area_name', type: 'string', example: 'Phân khu A'),
                                new OA\Property(property: 'code', type: 'string', example: 'A-01'),
                                new OA\Property(property: 'status', type: 'string', example: 'available'),
                                new OA\Property(property: 'area_size', type: 'number', format: 'float', nullable: true, example: 120.5),
                                new OA\Property(property: 'direction', type: 'string', nullable: true, example: 'Đông Nam'),
                                new OA\Property(property: 'price', type: 'integer', nullable: true, example: 5000000000),
                                new OA\Property(property: 'unit_price', type: 'integer', nullable: true, example: 45000000),
                                new OA\Property(property: 'coordinate_x', type: 'integer', nullable: true, example: 150),
                                new OA\Property(property: 'coordinate_y', type: 'integer', nullable: true, example: 320),
                                new OA\Property(property: 'width', type: 'integer', nullable: true, example: 60),
                                new OA\Property(property: 'height', type: 'integer', nullable: true, example: 60),
                                new OA\Property(property: 'image_url', type: 'string', nullable: true, example: 'https://example.com/lot.jpg'),
                                new OA\Property(property: 'frontage', type: 'number', format: 'float', nullable: true, example: 5.5),
                                new OA\Property(property: 'legal', type: 'string', nullable: true, example: 'Sổ hồng riêng'),
                                new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Mô tả chi tiết lô đất...'),
                                new OA\Property(
                                    property: 'planning',
                                    type: 'object',
                                    nullable: true,
                                    ref: '#/components/schemas/Planning'
                                ),
                                new OA\Property(
                                    property: 'comments',
                                    type: 'array',
                                    items: new OA\Items(
                                        properties: [
                                            new OA\Property(property: 'id', type: 'string', format: 'uuid', example: 'd3b07384-d113-4ec2-a5d6-c734b1234567'),
                                            new OA\Property(property: 'user_id', type: 'string', format: 'uuid', example: 'd3b07384-d113-4ec2-a5d6-c734b3234567'),
                                            new OA\Property(property: 'user_name', type: 'string', example: 'Nguyen Van A'),
                                            new OA\Property(property: 'content', type: 'string', example: 'Bình luận nội bộ...'),
                                            new OA\Property(property: 'created_at', type: 'string', format: 'date-time')
                                        ]
                                    )
                                )
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Chưa đăng nhập (Unauthenticated)'
            ),
            new OA\Response(
                response: 403,
                description: 'Không có quyền truy cập khu đất của lô đất này.',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Bạn không có quyền truy cập khu đất này.')
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Lô đất không tồn tại.',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Lô đất không tồn tại.')
                    ]
                )
            ),
            new OA\Response(
                response: 500,
                description: 'Lỗi hệ thống khi tải thông tin lô đất.'
            )
        ]
    )]
    public function lotDetail(string $id, \Illuminate\Http\Request $request): JsonResponse
    {
        $userId = (string) $request->user()->id;
        $result = $this->areaService->getLotDetail($userId, $id);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Post(
        path: '/api/v1/lots/{id}/comments',
        summary: 'Thêm bình luận nội bộ mới cho lô đất (UC-080)',
        description: 'Cho phép người dùng gửi bình luận nội bộ mới về lô đất.',
        security: [['bearerAuth' => []]],
        tags: ['Area'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'ID của lô đất',
                schema: new OA\Schema(type: 'string', format: 'uuid')
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['content'],
                properties: [
                    new OA\Property(property: 'content', type: 'string', example: 'Vị trí này rất tiềm năng.')
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Thêm bình luận thành công.',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Thêm bình luận thành công.'),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'string', format: 'uuid', example: 'd3b07384-d113-4ec2-a5d6-c734b1234567'),
                                new OA\Property(property: 'lot_id', type: 'string', format: 'uuid', example: 'd3b07384-d113-4ec2-a5d6-c734b2234567'),
                                new OA\Property(property: 'user_id', type: 'string', format: 'uuid', example: 'd3b07384-d113-4ec2-a5d6-c734b3234567'),
                                new OA\Property(property: 'user_name', type: 'string', example: 'Nguyen Van A'),
                                new OA\Property(property: 'content', type: 'string', example: 'Vị trí này rất tiềm năng.'),
                                new OA\Property(property: 'created_at', type: 'string', format: 'date-time')
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Chưa đăng nhập (Unauthenticated)'
            ),
            new OA\Response(
                response: 403,
                description: 'Không có quyền truy cập khu đất của lô đất này.',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Bạn không có quyền truy cập khu đất này.')
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Lô đất không tồn tại.',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Lô đất không tồn tại.')
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Dữ liệu không hợp lệ.',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Dữ liệu không hợp lệ.'),
                        new OA\Property(
                            property: 'errors',
                            type: 'object',
                            properties: [
                                new OA\Property(
                                    property: 'content',
                                    type: 'array',
                                    items: new OA\Items(type: 'string', example: 'Vui lòng nhập nội dung bình luận.')
                                )
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 500,
                description: 'Lỗi hệ thống khi thêm bình luận.'
            )
        ]
    )]
    public function addLotComment(CreateLotCommentRequest $request, string $id): JsonResponse
    {
        $userId = (string) $request->user()->id;
        $dto = CreateLotCommentDTO::fromRequest($request, $id, $userId);

        $result = $this->areaService->addLotComment($dto);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage(), 201);
    }

    #[OA\Post(
        path: '/api/v1/lots/{id}/lock',
        summary: 'Yêu cầu giữ chỗ (lock) lô đất (UC-082)',
        description: 'Cho phép nhân viên gửi yêu cầu giữ chỗ (lock) lô đất trong một khoảng thời gian.',
        security: [['bearerAuth' => []]],
        tags: ['Area'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'ID của lô đất',
                schema: new OA\Schema(type: 'string', format: 'uuid')
            )
        ],
        requestBody: new OA\RequestBody(
            required: false,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'reason', type: 'string', nullable: true, example: 'Khách hẹn cọc ngày mai.')
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Yêu cầu lock lô thành công.',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Yêu cầu lock lô thành công.'),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'string', format: 'uuid', example: 'd3b07384-d113-4ec2-a5d6-c734b1234567'),
                                new OA\Property(property: 'lot_id', type: 'string', format: 'uuid', example: 'd3b07384-d113-4ec2-a5d6-c734b2234567'),
                                new OA\Property(property: 'user_id', type: 'string', format: 'uuid', example: 'd3b07384-d113-4ec2-a5d6-c734b3234567'),
                                new OA\Property(property: 'reason', type: 'string', nullable: true, example: 'Khách hẹn cọc ngày mai.'),
                                new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                                new OA\Property(
                                    property: 'lot',
                                    type: 'object',
                                    properties: [
                                        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
                                        new OA\Property(property: 'code', type: 'string'),
                                        new OA\Property(property: 'status', type: 'string', example: 'reserved')
                                    ]
                                )
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Lô đất đã được lock hoặc bán.',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Lô đất đang được giữ chỗ.')
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Chưa đăng nhập (Unauthenticated)'
            ),
            new OA\Response(
                response: 403,
                description: 'Không có quyền thực hiện hoặc không có quyền truy cập khu đất của lô đất này.',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Bạn không có quyền thực hiện chức năng này.')
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Lô đất không tồn tại.',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Lô đất không tồn tại.')
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Dữ liệu không hợp lệ.'
            ),
            new OA\Response(
                response: 500,
                description: 'Lỗi hệ thống khi tạo yêu cầu lock lô.'
            )
        ]
    )]
    public function requestLockLot(RequestLockLotRequest $request, string $id): JsonResponse
    {
        $userId = (string) $request->user()->id;
        $dto = RequestLockLotDTO::fromRequest($request, $id, $userId);

        $result = $this->areaService->requestLockLot($dto);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Get(
        path: '/api/v1/areas/search',
        summary: 'Tìm kiếm khu đất hoặc lô đất trong inventory (UC-079)',
        description: 'Cho phép người dùng tìm kiếm khu đất, bảng hàng hoặc lô đất theo tên khu vực, mã lô hoặc thông tin liên quan.',
        security: [['bearerAuth' => []]],
        tags: ['Area'],
        parameters: [
            new OA\Parameter(
                name: 'keyword',
                in: 'query',
                required: true,
                description: 'Từ khóa tìm kiếm (Tên khu đất, tên khu vực, mã lô đất)',
                schema: new OA\Schema(type: 'string', minLength: 1)
            ),
            new OA\Parameter(
                name: 'per_page',
                in: 'query',
                required: false,
                description: 'Số bản ghi trên mỗi trang (nếu có)',
                schema: new OA\Schema(type: 'integer', default: 10)
            ),
            new OA\Parameter(
                name: 'page',
                in: 'query',
                required: false,
                description: 'Trang hiện tại (nếu có)',
                schema: new OA\Schema(type: 'integer', default: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Tìm kiếm dữ liệu bảng hàng thành công.',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Tìm kiếm dữ liệu bảng hàng thành công.'),
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'type', type: 'string', example: 'area'),
                                    new OA\Property(property: 'id', type: 'string', format: 'uuid', example: 'd3b07384-d113-4ec2-a5d6-c734b1234567'),
                                    new OA\Property(property: 'code', type: 'string', nullable: true, example: 'A-01'),
                                    new OA\Property(property: 'name', type: 'string', example: 'Phân khu A'),
                                    new OA\Property(property: 'sales_board_image', type: 'string', nullable: true, example: 'https://example.com/board.jpg'),
                                    new OA\Property(property: 'total_lots', type: 'integer', example: 100),
                                    new OA\Property(property: 'remaining_lots', type: 'integer', example: 45),
                                    new OA\Property(property: 'status', type: 'string', example: 'Đang mở bán'),
                                    new OA\Property(property: 'target_id', type: 'string', format: 'uuid', example: 'd3b07384-d113-4ec2-a5d6-c734b1234567')
                                ]
                            )
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Chưa đăng nhập (Unauthenticated)'
            ),
            new OA\Response(
                response: 403,
                description: 'Không có quyền truy cập dữ liệu này.',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Bạn không có quyền truy cập dữ liệu này.')
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Không tìm thấy bảng hàng hoặc lô đất phù hợp.',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Không tìm thấy bảng hàng hoặc lô đất phù hợp.')
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Vui lòng nhập từ khóa tìm kiếm.',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Dữ liệu không hợp lệ.'),
                        new OA\Property(
                            property: 'errors',
                            type: 'object',
                            properties: [
                                new OA\Property(
                                    property: 'keyword',
                                    type: 'array',
                                    items: new OA\Items(type: 'string', example: 'Vui lòng nhập từ khóa tìm kiếm.')
                                )
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 500,
                description: 'Không thể thực hiện tìm kiếm. Vui lòng thử lại.'
            )
        ]
    )]
    public function search(SearchInventoryRequest $request): JsonResponse
    {
        $userId = (string) $request->user()->id;
        $dto = SearchInventoryDTO::fromRequest($request);

        $result = $this->areaService->searchInventory($userId, $dto);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }
}

