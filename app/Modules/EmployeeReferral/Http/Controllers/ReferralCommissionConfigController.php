<?php

declare(strict_types=1);

namespace App\Modules\EmployeeReferral\Http\Controllers;

use App\Core\Controller\BaseController;
use App\Modules\EmployeeReferral\Http\Requests\UpdateReferralCommissionConfigRequest;
use App\Modules\EmployeeReferral\Interfaces\ReferralCommissionConfigServiceInterface;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

final class ReferralCommissionConfigController extends BaseController
{
    public function __construct(
        private readonly ReferralCommissionConfigServiceInterface $configService
    ) {
    }

    #[OA\Get(
        path: '/api/v1/employee-referrals/commission-configs',
        summary: 'Xem cấu hình hoa hồng referral (UC-103)',
        description: 'Lấy danh sách cấu hình hoa hồng referral hiện tại đang áp dụng trên hệ thống. Yêu cầu quyền: General Director hoặc Super Admin.',
        tags: ['Employee Referral'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Tải cấu hình hoa hồng referral thành công',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Tải cấu hình hoa hồng referral thành công.'),
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/ReferralCommissionConfig')
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Bạn không có quyền truy cập chức năng này.'),
            new OA\Response(response: 404, description: 'Cấu hình hoa hồng không tồn tại.')
        ]
    )]
    public function index(): JsonResponse
    {
        $result = $this->configService->getConfigs((string)request()->user()->id);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Put(
        path: '/api/v1/employee-referrals/commission-configs',
        summary: 'Cập nhật cấu hình hoa hồng referral (UC-104)',
        description: 'Cập nhật danh sách cấu hình hoa hồng referral. Yêu cầu quyền: General Director hoặc Super Admin.',
        tags: ['Employee Referral'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/UpdateReferralCommissionConfigRequest')
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Cập nhật cấu hình hoa hồng thành công',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Cập nhật cấu hình hoa hồng thành công.'),
                        new OA\Property(property: 'data', type: 'null'),
                    ]
                )
            ),
            new OA\Response(response: 400, description: 'Dữ liệu không hợp lệ.'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Bạn không có quyền truy cập chức năng này.')
        ]
    )]
    public function update(UpdateReferralCommissionConfigRequest $request): JsonResponse
    {
        $configs = $request->input('configs', []);
        $result = $this->configService->updateConfigs((string)$request->user()->id, $configs);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess(null, $result->getMessage());
    }
}
