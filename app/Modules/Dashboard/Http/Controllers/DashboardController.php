<?php

namespace App\Modules\Dashboard\Http\Controllers;

use App\Core\Controller\BaseController;
use App\Modules\Dashboard\DTO\ViewDashboardDTO;
use App\Modules\Dashboard\Interfaces\DashboardServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

final class DashboardController extends BaseController
{
    public function __construct(
        private readonly DashboardServiceInterface $dashboardService
    ) {
    }

    #[OA\Get(
        path: '/api/v1/dashboard',
        description: 'Lấy dữ liệu tổng quan cho trang chủ dựa trên quyền của người dùng.',
        summary: 'Xem màn hình Trang chủ (UC-06)',
        security: [['bearerAuth' => []]],
        tags: ['Dashboard'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Tải dữ liệu thành công',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Tải dữ liệu trang chủ thành công.'),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(
                                    property: 'user',
                                    properties: [
                                        new OA\Property(property: 'id', type: 'string'),
                                        new OA\Property(property: 'name', type: 'string'),
                                        new OA\Property(property: 'avatar', type: 'string', nullable: true),
                                        new OA\Property(property: 'role', type: 'integer')
                                    ],
                                    type: 'object'
                                ),
                                new OA\Property(
                                    property: 'overview',
                                    properties: [
                                        new OA\Property(property: 'latest_news', type: 'array', items: new OA\Items(type: 'object')),
                                        new OA\Property(property: 'kpi', type: 'object')
                                    ],
                                    type: 'object'
                                ),
                                new OA\Property(property: 'modules', type: 'array', items: new OA\Items(type: 'object'))
                            ],
                            type: 'object'
                        )
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Chưa đăng nhập'),
            new OA\Response(response: 403, description: 'Tài khoản bị khóa'),
            new OA\Response(response: 404, description: 'Không tìm thấy dữ liệu')
        ]
    )]
    /**
     * Xem màn hình Trang chủ (UC-06).
     * 
     * @param Request $request
     * @return JsonResponse
     * @throws \Throwable
     */
    public function index(Request $request): JsonResponse
    {
        $dto = ViewDashboardDTO::fromRequest($request);
        $result = $this->dashboardService->getViewData($dto);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }
}
