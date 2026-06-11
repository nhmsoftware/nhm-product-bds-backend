<?php

declare(strict_types=1);

namespace App\Modules\Auth\Http\Controllers;

use App\Core\Controller\BaseController;
use App\Modules\Auth\DTO\GetRewardPointHistoryDTO;
use App\Modules\Auth\Http\Requests\GetRewardPointHistoryRequest;
use App\Modules\Auth\Interfaces\AuthServiceInterface;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;
use Illuminate\Http\Request;

class RewardPointController extends BaseController
{
    public function __construct(
        private readonly AuthServiceInterface $authService
    ) {
    }

    #[OA\Get(
        path: '/api/v1/auth/reward-points/overview',
        summary: 'Lấy thông tin tổng quan điểm thưởng của nhân viên (UC-105)',
        security: [['bearerAuth' => []]],
        tags: ['Reward Points'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Tải dữ liệu tổng quan thành công.',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Tải dữ liệu tổng quan thành công.'),
                        new OA\Property(property: 'data', type: 'object', properties: [
                            new OA\Property(property: 'total_points', type: 'integer', example: 120),
                            new OA\Property(property: 'kpi_stars', type: 'integer', example: 5),
                            new OA\Property(
                                property: 'rank',
                                type: 'object',
                                properties: [
                                    new OA\Property(property: 'id', type: 'integer', example: 3),
                                    new OA\Property(property: 'label', type: 'string', example: 'Vàng'),
                                ]
                            ),
                            new OA\Property(property: 'current_month_points', type: 'integer', example: 20),
                            new OA\Property(property: 'quarter_progress_percent', type: 'number', format: 'float', example: 80.5),
                            new OA\Property(property: 'quarter_points', type: 'integer', example: 80),
                            new OA\Property(property: 'quarter_target', type: 'integer', example: 100),
                        ])
                    ]
                )
            ),
            new OA\Response(
                response: 403,
                description: 'Bạn không có quyền truy cập chức năng này.'
            )
        ]
    )]
    public function overview(Request $request): JsonResponse
    {
        $userId = (string) $request->user()->id;
        $result = $this->authService->getRewardPointOverview($userId);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Get(
        path: '/api/v1/auth/reward-points/history',
        summary: 'Lấy lịch sử điểm thưởng của nhân viên (UC-105)',
        security: [['bearerAuth' => []]],
        tags: ['Reward Points'],
        parameters: [
            new OA\Parameter(
                name: 'from_date',
                in: 'query',
                required: false,
                description: 'Ngày bắt đầu (Y-m-d)',
                schema: new OA\Schema(type: 'string', format: 'date')
            ),
            new OA\Parameter(
                name: 'to_date',
                in: 'query',
                required: false,
                description: 'Ngày kết thúc (Y-m-d)',
                schema: new OA\Schema(type: 'string', format: 'date')
            ),
            new OA\Parameter(
                name: 'per_page',
                in: 'query',
                required: false,
                description: 'Số lượng trên trang',
                schema: new OA\Schema(type: 'integer', default: 15)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Tải dữ liệu lịch sử điểm thưởng thành công.',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Tải dữ liệu lịch sử điểm thưởng thành công.'),
                        new OA\Property(property: 'data', type: 'object', properties: [
                            new OA\Property(property: 'data', type: 'array', items: new OA\Items(
                                type: 'object',
                                properties: [
                                    new OA\Property(property: 'id', type: 'string', format: 'uuid', example: 'uuid'),
                                    new OA\Property(property: 'user_id', type: 'string', format: 'uuid', example: 'uuid'),
                                    new OA\Property(property: 'points_changed', type: 'integer', example: 10),
                                    new OA\Property(property: 'stars_changed', type: 'integer', example: 1),
                                    new OA\Property(property: 'reason', type: 'string', example: '1 giao dịch công chứng thành công'),
                                    new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2026-05-25T10:00:00Z'),
                                ]
                            ))
                        ])
                    ]
                )
            ),
            new OA\Response(
                response: 403,
                description: 'Bạn không có quyền truy cập chức năng này.'
            )
        ]
    )]
    public function history(GetRewardPointHistoryRequest $request): JsonResponse
    {
        $dto = GetRewardPointHistoryDTO::fromRequest($request);
        $result = $this->authService->getRewardPointHistory($dto);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }
}
