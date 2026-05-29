<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\Http\Controllers;

use App\Core\Controller\BaseController;
use App\Modules\Dashboard\Interfaces\RevenueReportServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

final class RevenueReportController extends BaseController
{
    public function __construct(
        private readonly RevenueReportServiceInterface $revenueReportService
    ) {
    }

    #[OA\Get(
        path: '/api/v1/dashboard/revenue-reports',
        description: 'Lấy dữ liệu báo cáo doanh thu công ty dành cho Tổng giám đốc (CEO).',
        summary: 'Xem Revenue Reports (UC-112)',
        security: [['bearerAuth' => []]],
        tags: ['Dashboard'],
        parameters: [
            new OA\Parameter(
                name: 'start_date',
                description: 'Ngày bắt đầu (YYYY-MM-DD)',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', format: 'date')
            ),
            new OA\Parameter(
                name: 'end_date',
                description: 'Ngày kết thúc (YYYY-MM-DD)',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', format: 'date')
            ),
            new OA\Parameter(
                name: 'department',
                description: 'Lọc theo phòng ban (string)',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'project_id',
                description: 'Lọc theo ID dự án',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
            new OA\Parameter(
                name: 'area',
                description: 'Lọc theo khu vực của nhân sự (string)',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Tải báo cáo doanh thu thành công',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Tải báo cáo doanh thu thành công.'),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(
                                    property: 'overview',
                                    type: 'object',
                                    properties: [
                                        new OA\Property(property: 'total_revenue', type: 'integer'),
                                        new OA\Property(property: 'total_transactions', type: 'integer')
                                    ]
                                ),
                                new OA\Property(
                                    property: 'by_department',
                                    type: 'array',
                                    items: new OA\Items(
                                        properties: [
                                            new OA\Property(property: 'department_name', type: 'string'),
                                            new OA\Property(property: 'revenue', type: 'integer'),
                                            new OA\Property(property: 'transactions_count', type: 'integer')
                                        ],
                                        type: 'object'
                                    )
                                ),
                                new OA\Property(
                                    property: 'by_project',
                                    type: 'array',
                                    items: new OA\Items(
                                        properties: [
                                            new OA\Property(property: 'project_id', type: 'string'),
                                            new OA\Property(property: 'project_name', type: 'string'),
                                            new OA\Property(property: 'revenue', type: 'integer'),
                                            new OA\Property(property: 'transactions_count', type: 'integer')
                                        ],
                                        type: 'object'
                                    )
                                ),
                                new OA\Property(
                                    property: 'by_employee',
                                    type: 'array',
                                    items: new OA\Items(
                                        properties: [
                                            new OA\Property(property: 'user_id', type: 'string'),
                                            new OA\Property(property: 'user_name', type: 'string'),
                                            new OA\Property(property: 'revenue', type: 'integer'),
                                            new OA\Property(property: 'transactions_count', type: 'integer')
                                        ],
                                        type: 'object'
                                    )
                                ),
                                new OA\Property(
                                    property: 'charts',
                                    type: 'object',
                                    properties: [
                                        new OA\Property(
                                            property: 'by_month',
                                            type: 'array',
                                            items: new OA\Items(
                                                properties: [
                                                    new OA\Property(property: 'label', type: 'string', example: '2026-05'),
                                                    new OA\Property(property: 'revenue', type: 'integer')
                                                ],
                                                type: 'object'
                                            )
                                        ),
                                        new OA\Property(
                                            property: 'by_quarter',
                                            type: 'array',
                                            items: new OA\Items(
                                                properties: [
                                                    new OA\Property(property: 'label', type: 'string', example: '2026-Q2'),
                                                    new OA\Property(property: 'revenue', type: 'integer')
                                                ],
                                                type: 'object'
                                            )
                                        ),
                                        new OA\Property(
                                            property: 'by_year',
                                            type: 'array',
                                            items: new OA\Items(
                                                properties: [
                                                    new OA\Property(property: 'label', type: 'string', example: '2026'),
                                                    new OA\Property(property: 'revenue', type: 'integer')
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
     * Xem Revenue Reports (UC-112)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $dto = \App\Modules\Dashboard\DTO\ViewRevenueReportDTO::fromRequest($request);
        $result = $this->revenueReportService->getRevenueReports($dto);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }
}
