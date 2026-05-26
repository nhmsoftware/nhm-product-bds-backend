<?php

declare(strict_types=1);

namespace App\Modules\EmployeeReferral\Http\Controllers;

use App\Core\Controller\BaseController;
use App\Modules\EmployeeReferral\Services\ReferralQrService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class ReferralQrController extends BaseController
{
    public function __construct(
        private readonly ReferralQrService $referralQrService
    ) {}

    #[OA\Get(
        path: '/api/v1/employee-referrals/recruitment-qr',
        summary: 'Xem mã QR tuyển dụng (UC-098)',
        security: [['bearerAuth' => []]],
        tags: ['Employee Referral'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Tải mã QR tuyển dụng thành công',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Tải mã QR tuyển dụng thành công.'),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'qr_url', type: 'string', example: 'https://bdsapp.vn/storage/qrs/recruitment_ST-ABCXYZ.svg'),
                                new OA\Property(property: 'referral_code', type: 'string', example: 'ST-ABCXYZ'),
                                new OA\Property(property: 'description', type: 'string', example: 'Sử dụng mã này để giới thiệu nhân sự mới tham gia hệ thống.'),
                                new OA\Property(property: 'share_text', type: 'string', example: 'Hãy tham gia mạng lưới của chúng tôi trên BDS App! Mã giới thiệu của tôi: ST-ABCXYZ'),
                            ],
                            type: 'object'
                        ),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Lỗi chưa có mã giới thiệu',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Bạn chưa có mã giới thiệu tuyển dụng.'),
                        new OA\Property(property: 'error_code', type: 'integer', example: 404),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 500,
                description: 'Lỗi tải/tạo mã QR',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Không thể tạo mã QR tuyển dụng.'),
                        new OA\Property(property: 'error_code', type: 'integer', example: 500),
                    ],
                    type: 'object'
                )
            )
        ]
    )]
    public function getRecruitmentQr(Request $request): JsonResponse
    {
        $result = $this->referralQrService->getRecruitmentQr($request->user());

        if (!$result->isSuccess()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), 'Tải mã QR tuyển dụng thành công.');
    }

    #[OA\Get(
        path: '/api/v1/employee-referrals/customer-qr',
        summary: 'Xem mã QR giới thiệu khách hàng (UC-100)',
        security: [['bearerAuth' => []]],
        tags: ['Employee Referral'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Tải mã QR khách hàng thành công',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Tải mã QR khách hàng thành công.'),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'qr_url', type: 'string', example: 'https://bdsapp.vn/storage/qrs/customer_ST-ABCXYZ.svg'),
                                new OA\Property(property: 'referral_code', type: 'string', example: 'ST-ABCXYZ'),
                                new OA\Property(property: 'description', type: 'string', example: 'Sử dụng mã này để giới thiệu khách hàng tham gia hệ thống.'),
                                new OA\Property(property: 'share_text', type: 'string', example: 'Tìm hiểu các dự án hấp dẫn tại BDS App! Mã giới thiệu khách hàng của tôi: ST-ABCXYZ'),
                            ],
                            type: 'object'
                        ),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Lỗi chưa có mã giới thiệu khách hàng',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Bạn chưa có mã giới thiệu khách hàng.'),
                        new OA\Property(property: 'error_code', type: 'integer', example: 404),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 500,
                description: 'Lỗi tải/tạo mã QR khách hàng',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Không thể tạo mã QR khách hàng.'),
                        new OA\Property(property: 'error_code', type: 'integer', example: 500),
                    ],
                    type: 'object'
                )
            )
        ]
    )]
    public function getCustomerQr(Request $request): JsonResponse
    {
        $result = $this->referralQrService->getCustomerQr($request->user());

        if (!$result->isSuccess()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), 'Tải mã QR khách hàng thành công.');
    }
}
