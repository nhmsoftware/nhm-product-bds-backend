<?php

namespace App\Modules\CustomerMeeting\Http\Controllers;

use App\Core\Controller\BaseController;
use App\Modules\CustomerMeeting\DTO\CheckInMeetCustomerDTO;
use App\Modules\CustomerMeeting\Http\Requests\CheckInMeetCustomerRequest;
use App\Modules\CustomerMeeting\Interfaces\CustomerMeetingServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

final class CustomerMeetingController extends BaseController
{
    public function __construct(
        private readonly CustomerMeetingServiceInterface $customerMeetingService
    ) {
    }

    #[OA\Post(
        path: '/api/v1/customer-meetings/check-in',
        description: 'Cho phép nhân viên bán hàng thực hiện check-in hoạt động gặp khách hàng tại dự án bằng tọa độ GPS, chụp ảnh thực tế cùng khách và điền thông tin.',
        summary: 'Check-in hoạt động gặp khách hàng (UC-038)',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['customer_name', 'customer_phone', 'project_id', 'image', 'latitude', 'longitude'],
                    properties: [
                        new OA\Property(property: 'customer_name', type: 'string', example: 'Nguyễn Văn Khách', description: 'Họ và tên khách hàng'),
                        new OA\Property(property: 'customer_phone', type: 'string', example: '0901234567', description: 'Số điện thoại khách hàng (10 số Việt Nam)'),
                        new OA\Property(property: 'project_id', type: 'string', format: 'uuid', example: '22222222-2222-2222-2222-222222222222', description: 'ID của dự án khách quan tâm'),
                        new OA\Property(property: 'image', type: 'string', format: 'binary', description: 'Ảnh chụp thực tế cùng khách hàng tại dự án'),
                        new OA\Property(property: 'latitude', type: 'number', format: 'float', example: 10.7769, description: 'Vĩ độ vị trí hiện tại'),
                        new OA\Property(property: 'longitude', type: 'number', format: 'float', example: 106.7009, description: 'Kinh độ vị trí hiện tại'),
                    ]
                )
            )
        ),
        security: [['sanctum' => []]],
        tags: ['Customer Meeting'],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Check-in thành công',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Check-in gặp khách thành công.'),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'meeting', ref: '#/components/schemas/CustomerMeeting'),
                                new OA\Property(
                                    property: 'recent_meetings',
                                    type: 'array',
                                    items: new OA\Items(ref: '#/components/schemas/CustomerMeeting')
                                )
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Yêu cầu không hợp lệ (ví dụ: thiếu thông tin, số điện thoại sai định dạng, GPS không bật)',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Số điện thoại không hợp lệ.'),
                    ]
                )
            ),
            new OA\Response(
                response: 403,
                description: 'Không có quyền truy cập (tài khoản khóa hoặc không đúng quyền)',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Tài khoản của bạn đã bị khóa hoặc đang ngưng hoạt động.'),
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Lỗi xác thực dữ liệu',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Dữ liệu không hợp lệ.'),
                        new OA\Property(
                            property: 'errors',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'customer_phone', type: 'array', items: new OA\Items(type: 'string', example: 'Số điện thoại không hợp lệ.'))
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Chưa đăng nhập (Unauthenticated)'),
        ]
    )]
    public function checkIn(CheckInMeetCustomerRequest $request): JsonResponse
    {
        $dto = CheckInMeetCustomerDTO::fromRequest($request, $request->user()->id);
        $result = $this->customerMeetingService->checkInMeetCustomer($dto);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage(), 201);
    }

    #[OA\Get(
        path: '/api/v1/customer-meetings/recent',
        description: 'Lấy danh sách các hoạt động check-in gặp khách hàng gần đây nhất của nhân viên.',
        summary: 'Lấy danh sách hoạt động gặp khách gần đây (UC-038/UC-042)',
        security: [['sanctum' => []]],
        tags: ['Customer Meeting'],
        parameters: [
            new OA\Parameter(
                name: 'limit',
                in: 'query',
                required: false,
                description: 'Giới hạn số lượng hoạt động gần đây cần lấy hoặc truyền "all" để xem tất cả (A4)',
                schema: new OA\Schema(type: 'string', example: '5')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Tải danh sách thành công',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Tải danh sách hoạt động gần đây thành công.'),
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/CustomerMeeting')
                        )
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Chưa đăng nhập (Unauthenticated)'),
            new OA\Response(response: 403, description: 'Tài khoản bị khóa'),
        ]
    )]
    public function recent(Request $request): JsonResponse
    {
        $limit = $request->query('limit', 5);
        if ($limit === 'all') {
            $limit = 100;
        } else {
            $limit = (int) $limit;
        }

        $result = $this->customerMeetingService->getRecentMeetings($request->user()->id, $limit);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Get(
        path: '/api/v1/customer-meetings/{id}',
        description: 'Cho phép nhân viên xem thông tin chi tiết một hoạt động gặp khách hàng cụ thể theo ID.',
        summary: 'Xem chi tiết hoạt động gặp khách hàng (UC-042)',
        security: [['sanctum' => []]],
        tags: ['Customer Meeting'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'ID của hoạt động gặp khách hàng (UUID)',
                schema: new OA\Schema(type: 'string', format: 'uuid')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Tải chi tiết hoạt động thành công',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Tải chi tiết hoạt động thành công.'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/CustomerMeeting')
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Hoạt động không tồn tại hoặc đã bị xóa (A3)',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Hoạt động không tồn tại hoặc đã bị xóa.'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Chưa đăng nhập (Unauthenticated)'),
            new OA\Response(response: 403, description: 'Tài khoản bị khóa hoặc không đủ quyền'),
        ]
    )]
    public function show(string $id, Request $request): JsonResponse
    {
        $result = $this->customerMeetingService->getMeetingDetails($request->user()->id, $id);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }
}
