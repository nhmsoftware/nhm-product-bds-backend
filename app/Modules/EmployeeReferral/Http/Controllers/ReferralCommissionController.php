<?php

declare(strict_types=1);

namespace App\Modules\EmployeeReferral\Http\Controllers;

use App\Core\Controller\BaseController;
use App\Modules\EmployeeReferral\Http\Requests\ReferralCommissionRequest;
use App\Modules\EmployeeReferral\Interfaces\ReferralCommissionServiceInterface;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

final class ReferralCommissionController extends BaseController
{
    public function __construct(
        private readonly ReferralCommissionServiceInterface $commissionService
    ) {
    }

    #[OA\Get(
        path: '/api/v1/employee-referrals/commissions',
        summary: 'Xem danh sách hoa hồng referral (UC-097)',
        description: 'Lấy danh sách hoa hồng giới thiệu của nhân viên.',
        tags: ['Employee Referral'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', required: false, description: 'Trang hiện tại', schema: new OA\Schema(type: 'integer', example: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, description: 'Số bản ghi mỗi trang', schema: new OA\Schema(type: 'integer', example: 15)),
            new OA\Parameter(name: 'filters[referral_type]', in: 'query', required: false, description: 'Lọc theo loại QR (1: Tuyển dụng, 2: Giới thiệu khách hàng)', schema: new OA\Schema(type: 'integer', enum: [1, 2])),
            new OA\Parameter(name: 'filters[status]', in: 'query', required: false, description: 'Lọc theo trạng thái thanh toán (1: Chờ thanh toán, 2: Đã thanh toán)', schema: new OA\Schema(type: 'integer', enum: [1, 2])),
            new OA\Parameter(name: 'filters[search]', in: 'query', required: false, description: 'Tìm kiếm theo tên hoặc số điện thoại người được giới thiệu', schema: new OA\Schema(type: 'string', example: 'Nguyễn')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Tải dữ liệu hoa hồng referral thành công',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Tải dữ liệu hoa hồng referral thành công.'),
                        new OA\Property(
                            property: 'meta',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'total_commission', type: 'string', example: '15000000', description: 'Tổng số tiền hoa hồng của danh sách đã lọc'),
                            ]
                        ),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'current_page', type: 'integer', example: 1),
                                new OA\Property(property: 'total', type: 'integer', example: 100),
                                new OA\Property(
                                    property: 'data',
                                    type: 'array',
                                    items: new OA\Items(ref: '#/components/schemas/ReferralCommission')
                                ),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Bạn không có quyền truy cập chức năng này.')
        ]
    )]
    public function index(ReferralCommissionRequest $request): JsonResponse
    {
        $filter = $request->getFilterOptions();
        $result = $this->commissionService->getCommissions((string)$request->user()->id, $filter);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage(), $result->getCode(), $result->getMeta());
    }

    #[OA\Get(
        path: '/api/v1/employee-referrals/commissions/{id}',
        summary: 'Xem chi tiết thông tin hoa hồng referral (UC-097)',
        description: 'Lấy chi tiết bản ghi hoa hồng giới thiệu của nhân viên.',
        tags: ['Employee Referral'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'ID của hoa hồng referral',
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Tải chi tiết referral commission thành công',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Tải chi tiết referral commission thành công.'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/ReferralCommission'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Bạn không có quyền truy cập chức năng này.'),
            new OA\Response(response: 404, description: 'Thông tin hoa hồng không tồn tại.')
        ]
    )]
    public function detail(string $id): JsonResponse
    {
        $result = $this->commissionService->getDetail((string)request()->user()->id, $id);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Get(
        path: '/api/v1/employee-referrals/reports/commissions',
        summary: 'Xem báo cáo hoa hồng referral (UC-102)',
        description: 'Lấy báo cáo hoa hồng giới thiệu của nhân viên. Dành cho Giám đốc và Super Admin.',
        tags: ['Employee Referral'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', required: false, description: 'Trang hiện tại', schema: new OA\Schema(type: 'integer', example: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, description: 'Số bản ghi mỗi trang', schema: new OA\Schema(type: 'integer', example: 15)),
            new OA\Parameter(name: 'filters[referrer_id]', in: 'query', required: false, description: 'Lọc theo nhân viên', schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'filters[referral_type]', in: 'query', required: false, description: 'Lọc theo loại QR (1: Tuyển dụng, 2: Giới thiệu khách hàng)', schema: new OA\Schema(type: 'integer', enum: [1, 2])),
            new OA\Parameter(name: 'filters[date_from]', in: 'query', required: false, description: 'Từ ngày (Y-m-d)', schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'filters[date_to]', in: 'query', required: false, description: 'Đến ngày (Y-m-d)', schema: new OA\Schema(type: 'string', format: 'date')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Tải báo cáo hoa hồng referral thành công',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Tải báo cáo hoa hồng referral thành công.'),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'current_page', type: 'integer', example: 1),
                                new OA\Property(property: 'total', type: 'integer', example: 100),
                                new OA\Property(
                                    property: 'data',
                                    type: 'array',
                                    items: new OA\Items(
                                        properties: [
                                            new OA\Property(property: 'referrer_id', type: 'string', format: 'uuid'),
                                            new OA\Property(property: 'referrer_name', type: 'string', example: 'Nguyễn Văn A'),
                                            new OA\Property(property: 'referral_type', type: 'integer', example: 1),
                                            new OA\Property(property: 'referral_count', type: 'integer', example: 5),
                                            new OA\Property(property: 'total_commission', type: 'string', example: '2500000')
                                        ]
                                    )
                                ),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Bạn không có quyền truy cập chức năng này.'),
            new OA\Response(response: 404, description: 'Chưa có dữ liệu hoa hồng referral.')
        ]
    )]
    public function report(\App\Modules\EmployeeReferral\Http\Requests\ReferralCommissionReportRequest $request): JsonResponse
    {
        $filter = $request->getFilterOptions();
        $result = $this->commissionService->getReport($request->user(), $filter);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }
}
