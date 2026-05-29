<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\Http\Controllers;

use App\Core\Controller\BaseController;
use App\Modules\Dashboard\DTO\ViewEmployeeReportDTO;
use App\Modules\Dashboard\Interfaces\EmployeeReportServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

final class EmployeeReportController extends BaseController
{
    public function __construct(
        private readonly EmployeeReportServiceInterface $employeeReportService
    ) {
    }

    #[OA\Get(
        path: '/api/v1/dashboard/employee-reports',
        description: 'Lấy danh sách báo cáo hiệu suất của từng nhân viên thuộc phạm vi quản lý của Director.',
        summary: 'Xem báo cáo nhân viên (UC-109)',
        security: [['bearerAuth' => []]],
        tags: ['Dashboard'],
        parameters: [
            new OA\Parameter(
                name: 'department',
                description: 'Lọc theo phòng ban cụ thể',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'employee_id',
                description: 'Lọc theo một nhân viên cụ thể',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
            new OA\Parameter(
                name: 'start_date',
                description: 'Ngày bắt đầu để tính kết quả (YYYY-MM-DD)',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', format: 'date')
            ),
            new OA\Parameter(
                name: 'end_date',
                description: 'Ngày kết thúc để tính kết quả (YYYY-MM-DD)',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', format: 'date')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Tải báo cáo nhân viên thành công',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Tải báo cáo nhân viên thành công.'),
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'id', type: 'string', format: 'uuid'),
                                    new OA\Property(property: 'full_name', type: 'string', description: 'Họ tên nhân viên'),
                                    new OA\Property(property: 'department', type: 'string', nullable: true, description: 'Phòng ban'),
                                    new OA\Property(property: 'job_position', type: 'string', nullable: true, description: 'Chức vụ'),
                                    new OA\Property(property: 'total_kpi', type: 'integer', description: 'Tổng điểm KPI'),
                                    new OA\Property(property: 'successful_transactions', type: 'integer', description: 'Số giao dịch công chứng thành công'),
                                    new OA\Property(property: 'site_tours', type: 'integer', description: 'Số lượt dẫn khách'),
                                    new OA\Property(property: 'customer_meetings', type: 'integer', description: 'Số lượt gặp khách'),
                                    new OA\Property(property: 'referrals', type: 'integer', description: 'Số nhân sự giới thiệu'),
                                    new OA\Property(property: 'working_days', type: 'integer', description: 'Số ngày công'),
                                    new OA\Property(property: 'fixed_schedule_absences', type: 'integer', description: 'Số lần vắng lịch cố định')
                                ],
                                type: 'object'
                            )
                        )
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Chưa đăng nhập'),
            new OA\Response(response: 403, description: 'Tài khoản bị khóa hoặc không có quyền truy cập'),
            new OA\Response(response: 404, description: 'Không tìm thấy người dùng')
        ]
    )]
    /**
     * Xem báo cáo nhân viên (UC-109)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $dto = ViewEmployeeReportDTO::fromRequest($request);
        $result = $this->employeeReportService->getEmployeeReports($dto);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Get(
        path: '/api/v1/dashboard/department-reports',
        description: 'Lấy danh sách báo cáo tổng hợp theo phòng ban thuộc phạm vi quản lý của Director.',
        summary: 'Xem báo cáo phòng ban (UC-110)',
        security: [['bearerAuth' => []]],
        tags: ['Dashboard'],
        parameters: [
            new OA\Parameter(
                name: 'department',
                description: 'Lọc theo tên phòng ban cụ thể',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'start_date',
                description: 'Ngày bắt đầu để tính kết quả (YYYY-MM-DD)',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', format: 'date')
            ),
            new OA\Parameter(
                name: 'end_date',
                description: 'Ngày kết thúc để tính kết quả (YYYY-MM-DD)',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', format: 'date')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Tải báo cáo phòng ban thành công',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Tải báo cáo phòng ban thành công.'),
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'department_name', type: 'string', description: 'Tên phòng ban'),
                                    new OA\Property(property: 'total_employees', type: 'integer', description: 'Tổng số nhân viên'),
                                    new OA\Property(property: 'total_kpi', type: 'integer', description: 'Tổng điểm KPI phòng ban'),
                                    new OA\Property(property: 'successful_transactions', type: 'integer', description: 'Tổng số giao dịch công chứng thành công'),
                                    new OA\Property(property: 'site_tours', type: 'integer', description: 'Tổng lượt dẫn khách'),
                                    new OA\Property(property: 'customer_meetings', type: 'integer', description: 'Tổng lượt gặp khách'),
                                    new OA\Property(property: 'referrals', type: 'integer', description: 'Tổng referral nhân sự'),
                                    new OA\Property(property: 'working_days', type: 'integer', description: 'Tổng ngày công'),
                                    new OA\Property(property: 'fixed_schedule_absences', type: 'integer', description: 'Tổng số lần vắng lịch cố định')
                                ],
                                type: 'object'
                            )
                        )
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Chưa đăng nhập'),
            new OA\Response(response: 403, description: 'Tài khoản bị khóa hoặc không có quyền truy cập'),
            new OA\Response(response: 404, description: 'Không tìm thấy người dùng')
        ]
    )]
    /**
     * Xem báo cáo phòng ban (UC-110)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function departmentReports(Request $request): JsonResponse
    {
        $dto = \App\Modules\Dashboard\DTO\ViewDepartmentReportDTO::fromRequest($request);
        $result = $this->employeeReportService->getDepartmentReports($dto);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }
}
