<?php

declare(strict_types=1);

namespace App\Modules\EmployeeReferral\Http\Controllers;

use App\Core\Controller\BaseController;
use App\Modules\EmployeeReferral\DTO\ScanReferralDTO;
use App\Modules\EmployeeReferral\Http\Requests\ReferralHistoryRequest;
use App\Modules\EmployeeReferral\Http\Requests\ScanReferralRequest;
use App\Modules\EmployeeReferral\Interfaces\ReferralHistoryServiceInterface;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

final class ReferralHistoryController extends BaseController
{
    public function __construct(
        private readonly ReferralHistoryServiceInterface $referralService
    ) {
    }

    #[OA\Post(
        path: '/api/v1/employee-referrals/scan',
        summary: 'Ghi nhận lượt quét mã QR giới thiệu (Public)',
        description: 'Lưu thông tin khi có người quét mã QR tuyển dụng hoặc QR giới thiệu khách hàng của nhân viên.',
        tags: ['Employee Referral'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['referral_code', 'referral_type', 'name', 'phone'],
                properties: [
                    new OA\Property(property: 'referral_code', type: 'string', example: 'ST-ABCXYZ', description: 'Mã giới thiệu của nhân viên'),
                    new OA\Property(property: 'referral_type', type: 'integer', example: 1, description: '1: QR Tuyển dụng, 2: QR Giới thiệu khách hàng'),
                    new OA\Property(property: 'name', type: 'string', example: 'Nguyễn Văn Khách', description: 'Họ tên người quét QR'),
                    new OA\Property(property: 'phone', type: 'string', example: '0901234567', description: 'Số điện thoại người quét QR'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Lưu lượt quét QR thành công',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Lưu lượt quét QR thành công.'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/ReferralHistory'),
                    ]
                )
            ),
            new OA\Response(
                response: 200,
                description: 'Cập nhật lượt quét QR thành công (khi đã quét trước đó)',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Cập nhật lượt quét QR thành công.'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/ReferralHistory'),
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Mã giới thiệu không hợp lệ hoặc không tồn tại.'),
            new OA\Response(response: 422, description: 'Dữ liệu đầu vào không hợp lệ.')
        ]
    )]
    public function recordScan(ScanReferralRequest $request): JsonResponse
    {
        $dto = ScanReferralDTO::fromRequest($request);
        $result = $this->referralService->recordScan($dto);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Get(
        path: '/api/v1/employee-referrals/history',
        summary: 'Xem lịch sử danh sách referral (UC-096)',
        description: 'Lấy danh sách những người đã quét mã QR giới thiệu của nhân viên.',
        tags: ['Employee Referral'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', required: false, description: 'Trang hiện tại', schema: new OA\Schema(type: 'integer', example: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, description: 'Số bản ghi mỗi trang', schema: new OA\Schema(type: 'integer', example: 15)),
            new OA\Parameter(name: 'filters[referral_type]', in: 'query', required: false, description: 'Lọc theo loại QR (1: Tuyển dụng, 2: Giới thiệu khách hàng)', schema: new OA\Schema(type: 'integer', enum: [1, 2])),
            new OA\Parameter(name: 'filters[search]', in: 'query', required: false, description: 'Tìm kiếm theo tên hoặc số điện thoại', schema: new OA\Schema(type: 'string', example: '0901')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Tải lịch sử referral thành công',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Tải lịch sử referral thành công.'),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'current_page', type: 'integer', example: 1),
                                new OA\Property(property: 'total', type: 'integer', example: 100),
                                new OA\Property(
                                    property: 'data',
                                    type: 'array',
                                    items: new OA\Items(ref: '#/components/schemas/ReferralHistory')
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
    public function history(ReferralHistoryRequest $request): JsonResponse
    {
        $filter = $request->getFilterOptions();
        $result = $this->referralService->getHistory((string)$request->user()->id, $filter);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Get(
        path: '/api/v1/employee-referrals/history/{id}',
        summary: 'Xem chi tiết thông tin referral (UC-096)',
        description: 'Lấy chi tiết bản ghi quét mã QR giới thiệu của nhân viên.',
        tags: ['Employee Referral'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'ID của lịch sử referral',
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Tải chi tiết referral thành công',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Tải chi tiết referral thành công.'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/ReferralHistory'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Bạn không có quyền truy cập chức năng này.'),
            new OA\Response(response: 404, description: 'Thông tin referral không tồn tại.')
        ]
    )]
    public function detail(string $id): JsonResponse
    {
        $result = $this->referralService->getDetail((string)request()->user()->id, $id);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }
}
