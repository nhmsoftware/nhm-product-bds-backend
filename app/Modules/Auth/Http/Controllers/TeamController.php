<?php

declare(strict_types=1);

namespace App\Modules\Auth\Http\Controllers;

use App\Core\Controller\BaseController;
use App\Modules\Auth\DTO\GetTeamMembersDTO;
use App\Modules\Auth\Http\Requests\GetTeamMembersRequest;
use App\Modules\Auth\Interfaces\AuthServiceInterface;
use App\Modules\Auth\DTO\GetTeamKpiDTO;
use App\Modules\Auth\Http\Requests\GetTeamKpiRequest;
use App\Modules\Auth\DTO\GetEmployeeKpiDTO;
use App\Modules\Auth\Http\Requests\GetEmployeeKpiRequest;
use App\Modules\Auth\DTO\GetDepartmentRankingDTO;
use App\Modules\Auth\Http\Requests\GetDepartmentRankingRequest;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;
use Illuminate\Http\Request;

class TeamController extends BaseController
{
    public function __construct(
        private readonly AuthServiceInterface $authService
    ) {
    }

    #[OA\Get(
        path: '/api/v1/auth/team/overview',
        summary: 'Lấy thông tin tổng quan phòng ban/khu vực (UC-106)',
        security: [['bearerAuth' => []]],
        tags: ['Team'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Tải thông tin tổng quan thành công.',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Tải thông tin tổng quan thành công.'),
                        new OA\Property(property: 'data', type: 'object', properties: [
                            new OA\Property(property: 'team_name', type: 'string', example: 'Phòng Kinh Doanh 1'),
                            new OA\Property(property: 'description', type: 'string', example: 'Phòng ban/Khu vực Phòng Kinh Doanh 1'),
                            new OA\Property(property: 'member_count', type: 'integer', example: 10),
                            new OA\Property(property: 'manager_name', type: 'string', example: 'Nguyen Van A'),
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
        $result = $this->authService->getTeamOverview($userId);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Get(
        path: '/api/v1/auth/team/members',
        summary: 'Lấy danh sách nhân viên trong phòng ban/khu vực (UC-106)',
        security: [['bearerAuth' => []]],
        tags: ['Team'],
        parameters: [
            new OA\Parameter(
                name: 'search',
                in: 'query',
                required: false,
                description: 'Tìm kiếm theo tên hoặc mã nhân viên',
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'job_position',
                in: 'query',
                required: false,
                description: 'Lọc theo vị trí công việc',
                schema: new OA\Schema(type: 'string')
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
                description: 'Tải danh sách nhân viên thành công.',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Tải danh sách nhân viên thành công.'),
                        new OA\Property(property: 'data', type: 'object', properties: [
                            new OA\Property(property: 'data', type: 'array', items: new OA\Items(
                                type: 'object',
                                properties: [
                                    new OA\Property(property: 'id', type: 'string', format: 'uuid', example: 'uuid'),
                                    new OA\Property(property: 'staff_code', type: 'string', example: 'ST-ABCXYZ'),
                                    new OA\Property(property: 'name', type: 'string', example: 'Nguyen Van B'),
                                    new OA\Property(property: 'job_position', type: 'string', example: 'Nhân viên kinh doanh'),
                                    new OA\Property(property: 'phone', type: 'string', example: '0901234567'),
                                    new OA\Property(property: 'avatar', type: 'string', nullable: true),
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
    public function members(GetTeamMembersRequest $request): JsonResponse
    {
        $dto = GetTeamMembersDTO::fromRequest($request);
        $result = $this->authService->getTeamMembers($dto);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Get(
        path: '/api/v1/auth/team/kpi/overview',
        summary: 'Lấy thông tin tổng quan KPI phòng ban/khu vực (UC-107)',
        security: [['bearerAuth' => []]],
        tags: ['Team KPI'],
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
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Tải thông tin tổng quan KPI thành công.',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Tải thông tin tổng quan KPI thành công.'),
                        new OA\Property(property: 'data', type: 'object', properties: [
                            new OA\Property(property: 'total_kpi_points', type: 'number', example: 150.5),
                            new OA\Property(property: 'total_transactions', type: 'integer', example: 12),
                            new OA\Property(property: 'total_tours', type: 'integer', example: 15),
                            new OA\Property(property: 'total_meetings', type: 'integer', example: 20),
                            new OA\Property(property: 'total_referrals', type: 'integer', example: 8),
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
    public function kpiOverview(GetTeamKpiRequest $request): JsonResponse
    {
        $dto = GetTeamKpiDTO::fromRequest($request);
        $result = $this->authService->getTeamKpiOverview($dto);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Get(
        path: '/api/v1/auth/team/kpi/leaderboard',
        summary: 'Lấy bảng xếp hạng KPI đội nhóm/phòng ban (UC-107)',
        security: [['bearerAuth' => []]],
        tags: ['Team KPI'],
        parameters: [
            new OA\Parameter(
                name: 'search',
                in: 'query',
                required: false,
                description: 'Tìm kiếm theo họ tên hoặc mã nhân viên',
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'job_position',
                in: 'query',
                required: false,
                description: 'Lọc theo vị trí công việc',
                schema: new OA\Schema(type: 'string')
            ),
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
                description: 'Số lượng kết quả trên mỗi trang',
                schema: new OA\Schema(type: 'integer', default: 15)
            ),
            new OA\Parameter(
                name: 'page',
                in: 'query',
                required: false,
                description: 'Trang hiện tại',
                schema: new OA\Schema(type: 'integer', default: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Tải bảng xếp hạng KPI thành công.',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Tải bảng xếp hạng KPI thành công.'),
                        new OA\Property(property: 'data', type: 'object', properties: [
                            new OA\Property(property: 'current_page', type: 'integer', example: 1),
                            new OA\Property(property: 'total', type: 'integer', example: 10),
                            new OA\Property(
                                property: 'data',
                                type: 'array',
                                items: new OA\Items(
                                    type: 'object',
                                    properties: [
                                        new OA\Property(property: 'rank', type: 'integer', example: 1),
                                        new OA\Property(property: 'id', type: 'string', format: 'uuid', example: 'uuid'),
                                        new OA\Property(property: 'staff_code', type: 'string', example: 'ST-ABCXYZ'),
                                        new OA\Property(property: 'name', type: 'string', example: 'Nguyen Van B'),
                                        new OA\Property(property: 'job_position', type: 'string', example: 'Nhân viên kinh doanh'),
                                        new OA\Property(property: 'avatar', type: 'string', nullable: true),
                                        new OA\Property(property: 'total_kpi_points', type: 'number', example: 45.5),
                                        new OA\Property(property: 'successful_transactions', type: 'integer', example: 3),
                                        new OA\Property(property: 'kpi_stars', type: 'integer', example: 2),
                                    ]
                                )
                            ),
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
    public function kpiLeaderboard(GetTeamKpiRequest $request): JsonResponse
    {
        $dto = GetTeamKpiDTO::fromRequest($request);
        $result = $this->authService->getTeamKpiLeaderboard($dto);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Get(
        path: '/api/v1/auth/team/kpi/members/{id}',
        summary: 'Xem chi tiết KPI và lịch sử điểm thưởng của một nhân viên (UC-107)',
        security: [['bearerAuth' => []]],
        tags: ['Team KPI'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'ID của nhân viên',
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
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
                description: 'Số lượng lịch sử điểm thưởng trên mỗi trang',
                schema: new OA\Schema(type: 'integer', default: 15)
            ),
            new OA\Parameter(
                name: 'page',
                in: 'query',
                required: false,
                description: 'Trang hiện tại',
                schema: new OA\Schema(type: 'integer', default: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Tải chi tiết KPI nhân viên thành công.',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Tải chi tiết KPI nhân viên thành công.'),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(
                                    property: 'employee',
                                    type: 'object',
                                    properties: [
                                        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
                                        new OA\Property(property: 'staff_code', type: 'string', example: 'ST-ABCXYZ'),
                                        new OA\Property(property: 'name', type: 'string', example: 'Nguyen Van B'),
                                        new OA\Property(property: 'job_position', type: 'string', example: 'Nhân viên kinh doanh'),
                                        new OA\Property(property: 'avatar', type: 'string', nullable: true),
                                        new OA\Property(property: 'department', type: 'string', nullable: true),
                                        new OA\Property(property: 'area', type: 'string', nullable: true),
                                    ]
                                ),
                                new OA\Property(
                                    property: 'kpi_summary',
                                    type: 'object',
                                    properties: [
                                        new OA\Property(property: 'total_kpi_points', type: 'number', example: 45.5),
                                        new OA\Property(property: 'kpi_stars', type: 'integer', example: 2),
                                        new OA\Property(property: 'transactions_count', type: 'integer', example: 3),
                                        new OA\Property(property: 'tours_count', type: 'integer', example: 5),
                                        new OA\Property(property: 'meetings_count', type: 'integer', example: 4),
                                        new OA\Property(property: 'referrals_count', type: 'integer', example: 1),
                                        new OA\Property(property: 'work_days', type: 'integer', example: 22),
                                        new OA\Property(property: 'absences', type: 'integer', example: 1),
                                    ]
                                ),
                                new OA\Property(
                                    property: 'reward_history',
                                    type: 'object',
                                    properties: [
                                        new OA\Property(property: 'current_page', type: 'integer', example: 1),
                                        new OA\Property(
                                            property: 'data',
                                            type: 'array',
                                            items: new OA\Items(ref: '#/components/schemas/RewardPointHistory')
                                        ),
                                    ]
                                ),
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 403,
                description: 'Bạn không có quyền truy cập chức năng này.'
            ),
            new OA\Response(
                response: 404,
                description: 'Không tìm thấy thông tin nhân viên.'
            )
        ]
    )]
    public function employeeKpiDetails(string $id, GetEmployeeKpiRequest $request): JsonResponse
    {
        $dto = GetEmployeeKpiDTO::fromRequest($request, $id);
        $result = $this->authService->getEmployeeKpiDetails($dto);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Get(
        path: '/api/v1/auth/team/ranking/departments',
        summary: 'Lấy bảng xếp hạng phòng ban dựa trên điểm KPI (UC-108)',
        security: [['bearerAuth' => []]],
        tags: ['Team KPI'],
        parameters: [
            new OA\Parameter(
                name: 'month',
                in: 'query',
                required: false,
                description: 'Lọc theo tháng (1-12)',
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'quarter',
                in: 'query',
                required: false,
                description: 'Lọc theo quý (1-4)',
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'year',
                in: 'query',
                required: false,
                description: 'Lọc theo năm',
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'area',
                in: 'query',
                required: false,
                description: 'Lọc theo khu vực',
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'per_page',
                in: 'query',
                required: false,
                description: 'Số lượng phòng ban trên mỗi trang',
                schema: new OA\Schema(type: 'integer', default: 15)
            ),
            new OA\Parameter(
                name: 'page',
                in: 'query',
                required: false,
                description: 'Trang hiện tại',
                schema: new OA\Schema(type: 'integer', default: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Tải bảng xếp hạng phòng ban thành công.',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Tải bảng xếp hạng phòng ban thành công.'),
                        new OA\Property(property: 'data', type: 'object', properties: [
                            new OA\Property(property: 'current_page', type: 'integer', example: 1),
                            new OA\Property(property: 'total', type: 'integer', example: 5),
                            new OA\Property(
                                property: 'data',
                                type: 'array',
                                items: new OA\Items(
                                    type: 'object',
                                    properties: [
                                        new OA\Property(property: 'rank', type: 'integer', example: 1),
                                        new OA\Property(property: 'department', type: 'string', example: 'Phòng Kinh Doanh 1'),
                                        new OA\Property(property: 'total_kpi_points', type: 'number', example: 350.5),
                                        new OA\Property(property: 'successful_transactions', type: 'integer', example: 12),
                                        new OA\Property(property: 'kpi_stars', type: 'integer', example: 15),
                                    ]
                                )
                            )
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
    public function departmentRanking(GetDepartmentRankingRequest $request): JsonResponse
    {
        $dto = GetDepartmentRankingDTO::fromRequest($request);
        $result = $this->authService->getDepartmentRanking($dto);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Get(
        path: '/api/v1/auth/team/ranking/departments/{department}',
        summary: 'Xem chi tiết KPI của phòng ban (UC-108)',
        security: [['bearerAuth' => []]],
        tags: ['Team KPI'],
        parameters: [
            new OA\Parameter(
                name: 'department',
                in: 'path',
                required: true,
                description: 'Tên phòng ban (URL-encoded)',
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'month',
                in: 'query',
                required: false,
                description: 'Lọc theo tháng (1-12)',
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'quarter',
                in: 'query',
                required: false,
                description: 'Lọc theo quý (1-4)',
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'year',
                in: 'query',
                required: false,
                description: 'Lọc theo năm',
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Tải chi tiết KPI phòng ban thành công.',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Tải chi tiết KPI phòng ban thành công.'),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'department', type: 'string', example: 'Phòng Kinh Doanh 1'),
                                new OA\Property(
                                    property: 'kpi_summary',
                                    type: 'object',
                                    properties: [
                                        new OA\Property(property: 'total_kpi_points', type: 'number', example: 350.5),
                                        new OA\Property(property: 'total_transactions', type: 'integer', example: 12),
                                        new OA\Property(property: 'total_tours', type: 'integer', example: 15),
                                        new OA\Property(property: 'total_meetings', type: 'integer', example: 20),
                                        new OA\Property(property: 'total_referrals', type: 'integer', example: 8),
                                        new OA\Property(property: 'kpi_stars', type: 'integer', example: 15),
                                    ]
                                ),
                                new OA\Property(
                                    property: 'employee_ranking',
                                    type: 'array',
                                    items: new OA\Items(
                                        type: 'object',
                                        properties: [
                                            new OA\Property(property: 'rank', type: 'integer', example: 1),
                                            new OA\Property(property: 'id', type: 'string', format: 'uuid'),
                                            new OA\Property(property: 'staff_code', type: 'string', example: 'ST-ABCXYZ'),
                                            new OA\Property(property: 'name', type: 'string', example: 'Nguyen Van B'),
                                            new OA\Property(property: 'job_position', type: 'string', example: 'Nhân viên kinh doanh'),
                                            new OA\Property(property: 'avatar', type: 'string', nullable: true),
                                            new OA\Property(property: 'total_kpi_points', type: 'number', example: 45.5),
                                            new OA\Property(property: 'successful_transactions', type: 'integer', example: 3),
                                            new OA\Property(property: 'kpi_stars', type: 'integer', example: 2),
                                        ]
                                    )
                                )
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 403,
                description: 'Bạn không có quyền truy cập chức năng này.'
            ),
            new OA\Response(
                response: 404,
                description: 'Không tìm thấy dữ liệu phù hợp.'
            )
        ]
    )]
    public function departmentKpiDetails(string $department, GetDepartmentRankingRequest $request): JsonResponse
    {
        $dto = GetDepartmentRankingDTO::fromRequest($request);
        $result = $this->authService->getDepartmentKpiDetails(urldecode($department), $dto);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }
}
