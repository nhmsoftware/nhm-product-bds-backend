<?php

namespace App\Modules\SiteTour\Http\Controllers;

use App\Core\Controller\BaseController;
use App\Modules\SiteTour\DTO\CheckInSiteTourDTO;
use App\Modules\SiteTour\Http\Requests\CheckInSiteTourRequest;
use App\Modules\SiteTour\Interfaces\SiteTourServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

final class SiteTourController extends BaseController
{
    public function __construct(
        private readonly SiteTourServiceInterface $siteTourService
    ) {
    }

    #[OA\Post(
        path: '/api/v1/site-tours/check-in',
        description: 'Cho phép nhân viên check-in hoạt động dẫn khách tham quan thực tế tại dự án hoặc lô đất bằng tọa độ GPS, chụp ảnh minh chứng và điền mã lô/căn hộ.',
        summary: 'Check-in dẫn khách tham quan (UC-039)',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['project_id', 'unit_code', 'customer_name', 'image', 'latitude', 'longitude'],
                    properties: [
                        new OA\Property(property: 'project_id', type: 'string', format: 'uuid', example: '22222222-2222-2222-2222-222222222222', description: 'ID của dự án dẫn khách tới'),
                        new OA\Property(property: 'unit_code', type: 'string', example: 'Block A - Căn 12.05', description: 'Mã lô hoặc mã căn hộ dẫn tham quan'),
                        new OA\Property(property: 'customer_name', type: 'string', example: 'Nguyễn Văn Khách', description: 'Tên khách hàng tham quan'),
                        new OA\Property(property: 'image', type: 'string', format: 'binary', description: 'Ảnh chụp minh chứng thực tế dẫn khách tại dự án'),
                        new OA\Property(property: 'latitude', type: 'number', format: 'float', example: 10.7769, description: 'Vĩ độ GPS hiện tại'),
                        new OA\Property(property: 'longitude', type: 'number', format: 'float', example: 106.7009, description: 'Kinh độ GPS hiện tại'),
                    ]
                )
            )
        ),
        security: [['sanctum' => []]],
        tags: ['Site Tour'],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Check-in dẫn khách thành công',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Check-in dẫn khách thành công.'),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'site_tour', ref: '#/components/schemas/SiteTour'),
                                new OA\Property(
                                    property: 'recent_tours',
                                    type: 'array',
                                    items: new OA\Items(ref: '#/components/schemas/SiteTour')
                                )
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Yêu cầu không hợp lệ (thiếu thông tin, định dạng không đúng)',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Dữ liệu không hợp lệ.'),
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Lỗi xác thực dữ liệu hoặc GPS không nằm trong khu vực hợp lệ',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Bạn không nằm trong khu vực hợp lệ.'),
                        new OA\Property(
                            property: 'errors',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'project_id', type: 'array', items: new OA\Items(type: 'string', example: 'Vui lòng chọn dự án.'))
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 403,
                description: 'Không có quyền truy cập (tài khoản khóa hoặc không đúng vai trò)',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Tài khoản của bạn đã bị khóa hoặc đang ngưng hoạt động.'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Chưa đăng nhập (Unauthenticated)'),
        ]
    )]
    public function checkIn(CheckInSiteTourRequest $request): JsonResponse
    {
        $dto = CheckInSiteTourDTO::fromRequest($request, $request->user()->id);
        $result = $this->siteTourService->checkInSiteTour($dto);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage(), 201);
    }

    #[OA\Get(
        path: '/api/v1/site-tours/recent',
        description: 'Lấy danh sách các hoạt động dẫn khách gần đây nhất của nhân viên sale.',
        summary: 'Lấy danh sách dẫn khách gần đây (UC-039)',
        security: [['sanctum' => []]],
        tags: ['Site Tour'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Tải danh sách thành công',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Tải danh sách hoạt động dẫn khách thành công.'),
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/SiteTour')
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
        $limit = $request->query('limit', 20);
        if ($limit === 'all') {
            $limit = 100;
        } else {
            $limit = (int) $limit;
        }

        $result = $this->siteTourService->getRecentTours($request->user()->id, $limit);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Get(
        path: '/api/v1/site-tours/history',
        description: 'Cho phép nhân viên xem lịch sử các lượt dẫn khách của bản thân bao gồm thời gian thực hiện, mã lô/căn hộ, tên khách hàng và trạng thái hoạt động.',
        summary: 'Xem lịch sử dẫn khách tham quan (UC-041)',
        security: [['sanctum' => []]],
        tags: ['Site Tour'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Tải danh sách lịch sử thành công',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Tải danh sách lịch sử dẫn khách thành công.'),
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/SiteTour')
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 500,
                description: 'Lỗi tải dữ liệu từ máy chủ (A2)',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Không thể tải lịch sử dẫn khách. Vui lòng thử lại.'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Chưa đăng nhập (Unauthenticated)'),
            new OA\Response(response: 403, description: 'Tài khoản bị khóa hoặc không đủ quyền'),
        ]
    )]
    public function history(Request $request): JsonResponse
    {
        $filters = $request->only(['project_id', 'customer_name']);
        $result = $this->siteTourService->getSiteTourHistory($request->user()->id, $filters);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }
}
