<?php

namespace App\Modules\DepartmentTransfer\Http\Controllers;

use App\Core\Controller\BaseController;
use App\Modules\DepartmentTransfer\DTO\StoreDepartmentTransferRequestDTO;
use App\Modules\DepartmentTransfer\Http\Requests\StoreDepartmentTransferRequest;
use App\Modules\DepartmentTransfer\Http\Requests\ViewDepartmentTransferRequestsRequest;
use App\Modules\DepartmentTransfer\Http\Requests\RejectDepartmentTransferRequest;
use App\Modules\DepartmentTransfer\Interfaces\DepartmentTransferServiceInterface;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Department Transfers', description: 'API quản lý các yêu cầu chuyển phòng ban')]
class DepartmentTransferController extends BaseController
{
    protected DepartmentTransferServiceInterface $departmentTransferService;

    public function __construct(DepartmentTransferServiceInterface $departmentTransferService)
    {
        $this->departmentTransferService = $departmentTransferService;
    }

    #[OA\Post(
        path: '/api/v1/department-transfers',
        summary: 'Tạo yêu cầu chuyển phòng ban (UC-049)',
        description: 'Cho phép nhân viên gửi yêu cầu chuyển sang phòng ban khác.',
        security: [['bearerAuth' => []]],
        tags: ['Department Transfers'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/StoreDepartmentTransferRequest')
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Gửi yêu cầu chuyển phòng ban thành công.',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Gửi yêu cầu chuyển phòng ban thành công.'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/DepartmentTransferRequest')
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Lỗi xác thực dữ liệu hoặc yêu cầu không hợp lệ.',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Bạn đang có yêu cầu chuyển phòng ban chờ xử lý.')
                    ]
                )
            ),
            new OA\Response(
                response: 500,
                description: 'Lỗi server.',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Không thể gửi yêu cầu chuyển phòng ban. Vui lòng thử lại.')
                    ]
                )
            )
        ]
    )]
    public function store(StoreDepartmentTransferRequest $request): JsonResponse
    {
        $dto = new StoreDepartmentTransferRequestDTO(array_merge($request->validated(), [
            'user_id' => $request->user()->id,
            'current_department' => (string) ($request->user()->department ?? ''),
        ]));

        $result = $this->departmentTransferService->createDepartmentTransferRequest($dto);

        if (!$result->isSuccess()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage(), 201);
    }

    #[OA\Get(
        path: '/api/v1/department-transfers',
        summary: 'Xem danh sách yêu cầu chuyển phòng ban (UC-050)',
        description: 'Cho phép Director hoặc Admin xem danh sách yêu cầu chuyển phòng ban của nhân viên có phân trang và lọc theo trạng thái.',
        security: [['bearerAuth' => []]],
        tags: ['Department Transfers'],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', required: false, description: 'Trang hiện tại (bắt đầu từ 1)', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, description: 'Số bản ghi trên mỗi trang', schema: new OA\Schema(type: 'integer', default: 10)),
            new OA\Parameter(name: 'sort_by', in: 'query', required: false, description: 'Trường dùng để sắp xếp', schema: new OA\Schema(type: 'string', enum: ['created_at', 'desired_transfer_date'], default: 'created_at')),
            new OA\Parameter(name: 'direction', in: 'query', required: false, description: 'Chiều sắp xếp', schema: new OA\Schema(type: 'string', enum: ['asc', 'desc'], default: 'desc')),
            new OA\Parameter(name: 'filters[status]', in: 'query', required: false, description: 'Lọc theo trạng thái xử lý', schema: new OA\Schema(type: 'string', enum: ['pending', 'approved', 'rejected'])),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Tải danh sách yêu cầu chuyển phòng ban thành công.',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Tải danh sách yêu cầu chuyển phòng ban thành công.'),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'current_page', type: 'integer', example: 1),
                                new OA\Property(
                                    property: 'data',
                                    type: 'array',
                                    items: new OA\Items(
                                        properties: [
                                            new OA\Property(property: 'id', type: 'string', format: 'uuid', example: 'd3b07384-d113-4ec2-a5d6-c734b1234567'),
                                            new OA\Property(property: 'user_id', type: 'string', format: 'uuid', example: 'a1b2c3d4-e5f6-7a8b-9c0d-1e2f3a4b5c6d'),
                                            new OA\Property(property: 'employee_name', type: 'string', example: 'Nguyễn Văn A'),
                                            new OA\Property(property: 'current_department', type: 'string', example: 'Phòng Kỹ thuật'),
                                            new OA\Property(property: 'target_department', type: 'string', example: 'Phòng Kinh doanh'),
                                            new OA\Property(property: 'desired_transfer_date', type: 'string', format: 'date', example: '2026-06-01'),
                                            new OA\Property(property: 'reason', type: 'string', example: 'Muốn thử thách ở lĩnh vực mới'),
                                            new OA\Property(property: 'status', type: 'integer', example: \App\Modules\DepartmentTransfer\Models\Enums\RequestStatus::PENDING->value),
                                            new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2026-05-19T08:15:23+07:00'),
                                        ],
                                        type: 'object'
                                    )
                                ),
                                new OA\Property(property: 'first_page_url', type: 'string', example: 'http://localhost/api/v1/department-transfers?page=1'),
                                new OA\Property(property: 'from', type: 'integer', example: 1),
                                new OA\Property(property: 'last_page', type: 'integer', example: 1),
                                new OA\Property(property: 'last_page_url', type: 'string', example: 'http://localhost/api/v1/department-transfers?page=1'),
                                new OA\Property(property: 'next_page_url', type: 'string', nullable: true, example: null),
                                new OA\Property(property: 'path', type: 'string', example: 'http://localhost/api/v1/department-transfers'),
                                new OA\Property(property: 'per_page', type: 'integer', example: 10),
                                new OA\Property(property: 'prev_page_url', type: 'string', nullable: true, example: null),
                                new OA\Property(property: 'to', type: 'integer', example: 2),
                                new OA\Property(property: 'total', type: 'integer', example: 2)
                            ],
                            type: 'object'
                        )
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Chưa đăng nhập (Unauthenticated)'),
            new OA\Response(response: 403, description: 'Không có quyền truy cập dữ liệu'),
            new OA\Response(response: 500, description: 'Không thể tải danh sách do lỗi kết nối CSDL hoặc máy chủ'),
        ]
    )]
    public function index(ViewDepartmentTransferRequestsRequest $request): JsonResponse
    {
        $filter = $request->getFilterOptions();
        $result = $this->departmentTransferService->getDepartmentTransferRequests($request->user()->id, $filter);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Get(
        path: '/api/v1/department-transfers/history',
        summary: 'Xem lịch sử xin phép chuyển phòng ban của nhân viên',
        description: 'Cho phép nhân viên xem các yêu cầu chuyển phòng ban đã gửi.',
        security: [['bearerAuth' => []]],
        tags: ['Department Transfers'],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', required: false, description: 'Trang hiện tại (bắt đầu từ 1)', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, description: 'Số bản ghi trên mỗi trang', schema: new OA\Schema(type: 'integer', default: 10)),
            new OA\Parameter(name: 'sort_by', in: 'query', required: false, description: 'Trường dùng để sắp xếp', schema: new OA\Schema(type: 'string', enum: ['created_at', 'desired_transfer_date'], default: 'created_at')),
            new OA\Parameter(name: 'direction', in: 'query', required: false, description: 'Chiều sắp xếp', schema: new OA\Schema(type: 'string', enum: ['asc', 'desc'], default: 'desc')),
            new OA\Parameter(name: 'filters[status]', in: 'query', required: false, description: 'Lọc theo trạng thái xử lý', schema: new OA\Schema(type: 'string', enum: ['pending', 'approved', 'rejected'])),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Tải lịch sử xin phép chuyển phòng ban thành công.'),
            new OA\Response(response: 401, description: 'Chưa đăng nhập (Unauthenticated)'),
            new OA\Response(response: 500, description: 'Không thể tải lịch sử xin phép chuyển phòng ban.'),
        ]
    )]
    public function history(ViewDepartmentTransferRequestsRequest $request): JsonResponse
    {
        $filter = $request->getFilterOptions();
        $result = $this->departmentTransferService->getEmployeeDepartmentTransferHistory($request->user()->id, $filter);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Put(
        path: '/api/v1/department-transfers/{id}/approve',
        summary: 'Duyệt yêu cầu chuyển phòng ban (UC-051)',
        description: 'Cho phép Director phê duyệt yêu cầu chuyển phòng ban của nhân viên.',
        security: [['bearerAuth' => []]],
        tags: ['Department Transfers'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'ID của yêu cầu chuyển phòng ban cần duyệt (UUID)',
                schema: new OA\Schema(type: 'string', format: 'uuid', example: 'd3b07384-d113-4ec2-a5d6-c734b1234567')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Duyệt yêu cầu chuyển phòng ban thành công.',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Duyệt yêu cầu chuyển phòng ban thành công.'),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'id', type: 'string', format: 'uuid', example: 'd3b07384-d113-4ec2-a5d6-c734b1234567'),
                                new OA\Property(property: 'user_id', type: 'string', format: 'uuid', example: 'a1b2c3d4-e5f6-7a8b-9c0d-1e2f3a4b5c6d'),
                                new OA\Property(property: 'current_department', type: 'string', example: 'Phòng Kỹ thuật'),
                                new OA\Property(property: 'target_department', type: 'string', example: 'Phòng Kinh doanh'),
                                new OA\Property(property: 'desired_transfer_date', type: 'string', format: 'date', example: '2026-06-01'),
                                new OA\Property(property: 'reason', type: 'string', example: 'Muốn thử thách ở lĩnh vực mới'),
                                new OA\Property(property: 'status', type: 'integer', example: \App\Modules\DepartmentTransfer\Models\Enums\RequestStatus::APPROVED->value),
                            ],
                            type: 'object'
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Yêu cầu không hợp lệ hoặc đã được xử lý.',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Yêu cầu đã được xử lý.')
                    ]
                )
            ),
            new OA\Response(
                response: 403,
                description: 'Không có quyền duyệt yêu cầu chuyển phòng ban hoặc tài khoản bị khóa.',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Bạn không có quyền duyệt yêu cầu chuyển phòng ban.')
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Không tìm thấy yêu cầu chuyển phòng ban hoặc người dùng.',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Không tìm thấy yêu cầu chuyển phòng ban.')
                    ]
                )
            ),
            new OA\Response(
                response: 500,
                description: 'Lỗi server.',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Không thể duyệt yêu cầu chuyển phòng ban. Vui lòng thử lại.')
                    ]
                )
            )
        ]
    )]
    public function approve(string $id): JsonResponse
    {
        $result = $this->departmentTransferService->approveDepartmentTransferRequest(
            request()->user()->id,
            $id
        );

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Put(
        path: '/api/v1/department-transfers/{id}/reject',
        summary: 'Từ chối yêu cầu chuyển phòng ban (UC-052)',
        description: 'Cho phép Director từ chối yêu cầu chuyển phòng ban của nhân viên với lý do cụ thể.',
        security: [['bearerAuth' => []]],
        tags: ['Department Transfers'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'ID của yêu cầu chuyển phòng ban cần từ chối (UUID)',
                schema: new OA\Schema(type: 'string', format: 'uuid', example: 'd3b07384-d113-4ec2-a5d6-c734b1234567')
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            description: 'Lý do từ chối yêu cầu',
            content: new OA\JsonContent(
                required: ['reason'],
                properties: [
                    new OA\Property(property: 'reason', type: 'string', maxLength: 1000, example: 'Phòng ban hiện tại đang thiếu nhân sự trầm trọng, chưa thể điều chuyển.')
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Từ chối yêu cầu chuyển phòng ban thành công.',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Từ chối yêu cầu chuyển phòng ban thành công.'),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'id', type: 'string', format: 'uuid', example: 'd3b07384-d113-4ec2-a5d6-c734b1234567'),
                                new OA\Property(property: 'user_id', type: 'string', format: 'uuid', example: 'a1b2c3d4-e5f6-7a8b-9c0d-1e2f3a4b5c6d'),
                                new OA\Property(property: 'current_department', type: 'string', example: 'Phòng Kỹ thuật'),
                                new OA\Property(property: 'target_department', type: 'string', example: 'Phòng Kinh doanh'),
                                new OA\Property(property: 'desired_transfer_date', type: 'string', format: 'date', example: '2026-06-01'),
                                new OA\Property(property: 'reason', type: 'string', example: 'Muốn thử thách ở lĩnh vực mới'),
                                new OA\Property(property: 'status', type: 'integer', example: \App\Modules\DepartmentTransfer\Models\Enums\RequestStatus::REJECTED->value),
                                new OA\Property(property: 'rejection_reason', type: 'string', example: 'Phòng ban hiện tại đang thiếu nhân sự trầm trọng, chưa thể điều chuyển.'),
                            ],
                            type: 'object'
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Yêu cầu không hợp lệ hoặc đã được xử lý.',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Yêu cầu đã được xử lý.')
                    ]
                )
            ),
            new OA\Response(
                response: 403,
                description: 'Không có quyền từ chối yêu cầu chuyển phòng ban hoặc tài khoản bị khóa.',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Bạn không có quyền từ chối yêu cầu chuyển phòng ban.')
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Không tìm thấy yêu cầu chuyển phòng ban hoặc người dùng.',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Không tìm thấy yêu cầu chuyển phòng ban.')
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Dữ liệu không hợp lệ (ví dụ: thiếu lý do từ chối).',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Vui lòng nhập lý do từ chối.')
                    ]
                )
            ),
            new OA\Response(
                response: 500,
                description: 'Lỗi server.',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Không thể từ chối yêu cầu chuyển phòng ban. Vui lòng thử lại.')
                    ]
                )
            )
        ]
    )]
    public function reject(RejectDepartmentTransferRequest $request, string $id): JsonResponse
    {
        $result = $this->departmentTransferService->rejectDepartmentTransferRequest(
            $request->user()->id,
            $id,
            $request->validated('reason')
        );

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }
}
