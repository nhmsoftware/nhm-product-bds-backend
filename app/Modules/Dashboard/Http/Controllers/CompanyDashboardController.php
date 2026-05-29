<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\Http\Controllers;

use App\Core\Controller\BaseController;
use App\Modules\Dashboard\Interfaces\CompanyDashboardServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

final class CompanyDashboardController extends BaseController
{
    public function __construct(
        private readonly CompanyDashboardServiceInterface $companyDashboardService
    ) {
    }

    #[OA\Get(
        path: '/api/v1/dashboard/company',
        description: 'Lấy dữ liệu dashboard tổng quan công ty dành cho Tổng giám đốc (CEO).',
        summary: 'Xem Company Dashboard (UC-111)',
        security: [['bearerAuth' => []]],
        tags: ['Dashboard'],
        parameters: [
            new OA\Parameter(
                name: 'month',
                description: 'Lọc theo tháng (1-12)',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'quarter',
                description: 'Lọc theo quý (1-4)',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'year',
                description: 'Lọc theo năm (VD: 2026)',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'area',
                description: 'Lọc theo khu vực (string)',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Tải báo cáo dashboard tổng quan thành công',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Tải báo cáo dashboard tổng quan thành công.'),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(
                                    property: 'overview',
                                    type: 'object',
                                    properties: [
                                        new OA\Property(property: 'total_employees', type: 'integer'),
                                        new OA\Property(property: 'total_departments', type: 'integer'),
                                        new OA\Property(property: 'total_transactions', type: 'integer'),
                                        new OA\Property(property: 'total_revenue', type: 'integer'),
                                        new OA\Property(property: 'total_customers', type: 'integer'),
                                        new OA\Property(property: 'total_referrals', type: 'integer'),
                                        new OA\Property(property: 'total_kpi', type: 'integer')
                                    ]
                                ),
                                new OA\Property(
                                    property: 'department_stats',
                                    type: 'array',
                                    items: new OA\Items(
                                        properties: [
                                            new OA\Property(property: 'department_name', type: 'string'),
                                            new OA\Property(property: 'total_kpi', type: 'integer'),
                                            new OA\Property(property: 'successful_transactions', type: 'integer'),
                                            new OA\Property(property: 'total_revenue', type: 'integer')
                                        ],
                                        type: 'object'
                                    )
                                ),
                                new OA\Property(
                                    property: 'leaderboards',
                                    type: 'object',
                                    properties: [
                                        new OA\Property(
                                            property: 'top_employees_by_kpi',
                                            type: 'array',
                                            items: new OA\Items(
                                                properties: [
                                                    new OA\Property(property: 'user_id', type: 'string'),
                                                    new OA\Property(property: 'name', type: 'string'),
                                                    new OA\Property(property: 'department', type: 'string'),
                                                    new OA\Property(property: 'job_position', type: 'string'),
                                                    new OA\Property(property: 'total_kpi', type: 'integer')
                                                ],
                                                type: 'object'
                                            )
                                        ),
                                        new OA\Property(
                                            property: 'top_departments_by_kpi',
                                            type: 'array',
                                            items: new OA\Items(
                                                properties: [
                                                    new OA\Property(property: 'department_name', type: 'string'),
                                                    new OA\Property(property: 'total_kpi', type: 'integer'),
                                                    new OA\Property(property: 'successful_transactions', type: 'integer'),
                                                    new OA\Property(property: 'total_revenue', type: 'integer')
                                                ],
                                                type: 'object'
                                            )
                                        )
                                    ]
                                )
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Chưa đăng nhập'),
            new OA\Response(response: 403, description: 'Tài khoản bị khóa hoặc không có quyền truy cập')
        ]
    )]
    /**
     * Xem Company Dashboard (UC-111)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $dto = \App\Modules\Dashboard\DTO\ViewCompanyDashboardDTO::fromRequest($request);
        $result = $this->companyDashboardService->getCompanyDashboard($dto);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }
}
