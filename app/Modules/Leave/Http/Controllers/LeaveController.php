<?php

namespace App\Modules\Leave\Http\Controllers;

use App\Core\Controller\BaseController;
use App\Modules\Leave\DTO\CreateLeaveDTO;
use App\Modules\Leave\Http\Requests\CreateLeaveRequest;
use App\Modules\Leave\Http\Requests\LeaveHistoryRequest;
use App\Modules\Leave\Http\Requests\RejectLeaveRequest;
use App\Modules\Leave\Http\Requests\ViewLeaveRequestsRequest;
use App\Modules\Leave\Interfaces\LeaveServiceInterface;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

final class LeaveController extends BaseController
{
    /**
     * Khởi tạo Controller và inject LeaveService qua Interface.
     *
     * @param LeaveServiceInterface $leaveService
     */
    public function __construct(
        private readonly LeaveServiceInterface $leaveService
    ) {
    }

    #[OA\Post(
        path: '/api/v1/leave/requests',
        summary: 'Tạo đơn xin nghỉ phép mới (UC-043)',
        description: 'Cho phép nhân viên gửi yêu cầu xin nghỉ phép trên hệ thống bao gồm loại nghỉ phép, thời gian nghỉ và lý do nghỉ.',
        tags: ['Leave'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['leave_type', 'start_date', 'end_date', 'reason'],
                properties: [
                    new OA\Property(
                        property: 'leave_type',
                        type: 'string',
                        enum: ['annual', 'unpaid', 'personal', 'maternity', 'business', 'compensatory'],
                        example: 'annual',
                        description: 'Loại nghỉ phép: annual (phép năm), unpaid (không lương), personal (cá nhân), maternity (thai sản), business (công tác), compensatory (bù)'
                    ),
                    new OA\Property(property: 'start_date', type: 'string', format: 'date', example: '2026-05-20', description: 'Ngày bắt đầu nghỉ phép (Y-m-d)'),
                    new OA\Property(property: 'end_date', type: 'string', format: 'date', example: '2026-05-22', description: 'Ngày kết thúc nghỉ phép (Y-m-d)'),
                    new OA\Property(property: 'reason', type: 'string', example: 'Có việc gia đình đột xuất cần giải quyết', description: 'Lý do xin nghỉ phép')
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Gửi yêu cầu nghỉ phép thành công',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Gửi yêu cầu nghỉ phép thành công.'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/LeaveRequest'),
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Yêu cầu không hợp lệ (ngày nghỉ bị trùng/chồng lấp hoặc ngày kết thúc < ngày bắt đầu)',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Bạn đã có yêu cầu nghỉ phép trong khoảng thời gian này.'),
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Lỗi xác thực dữ liệu đầu vào (ví dụ: thiếu thông tin hoặc sai định dạng ngày)',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Dữ liệu không hợp lệ.'),
                        new OA\Property(
                            property: 'errors',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'leave_type', type: 'array', items: new OA\Items(type: 'string', example: 'Vui lòng nhập đầy đủ thông tin nghỉ phép.'))
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Chưa đăng nhập (Unauthenticated)'),
            new OA\Response(response: 403, description: 'Tài khoản của người dùng đã bị khóa hoặc không có quyền gửi đơn'),
            new OA\Response(response: 500, description: 'Không thể gửi đơn do lỗi kết nối CSDL hoặc sự cố máy chủ'),
        ]
    )]
    public function store(CreateLeaveRequest $request): JsonResponse
    {
        $dto = CreateLeaveDTO::fromRequest($request, $request->user()->id);
        $result = $this->leaveService->createLeaveRequest($dto);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Get(
        path: '/api/v1/leave/history',
        summary: 'Xem lịch sử các yêu cầu nghỉ phép (UC-044)',
        description: 'Cho phép nhân viên xem danh sách lịch sử các yêu cầu nghỉ phép đã gửi có hỗ trợ phân trang, lọc và sắp xếp.',
        tags: ['Leave'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', required: false, description: 'Trang hiện tại (bắt đầu từ 1)', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, description: 'Số bản ghi trên mỗi trang', schema: new OA\Schema(type: 'integer', default: 10)),
            new OA\Parameter(name: 'sort_by', in: 'query', required: false, description: 'Trường dùng để sắp xếp', schema: new OA\Schema(type: 'string', enum: ['created_at', 'start_date', 'end_date'], default: 'created_at')),
            new OA\Parameter(name: 'direction', in: 'query', required: false, description: 'Chiều sắp xếp', schema: new OA\Schema(type: 'string', enum: ['asc', 'desc'], default: 'desc')),
            new OA\Parameter(name: 'filters[leave_type]', in: 'query', required: false, description: 'Lọc theo loại nghỉ phép', schema: new OA\Schema(type: 'string', enum: ['annual', 'unpaid', 'personal', 'maternity', 'business', 'compensatory'])),
            new OA\Parameter(name: 'filters[status]', in: 'query', required: false, description: 'Lọc theo trạng thái xử lý', schema: new OA\Schema(type: 'string', enum: ['pending', 'approved', 'rejected'])),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Tải lịch sử nghỉ phép thành công',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Tải lịch sử nghỉ phép thành công.'),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'current_page', type: 'integer', example: 1),
                                new OA\Property(
                                    property: 'data',
                                    type: 'array',
                                    items: new OA\Items(ref: '#/components/schemas/LeaveRequest')
                                ),
                                new OA\Property(property: 'first_page_url', type: 'string', example: 'http://localhost/api/v1/leave/history?page=1'),
                                new OA\Property(property: 'from', type: 'integer', example: 1),
                                new OA\Property(property: 'last_page', type: 'integer', example: 1),
                                new OA\Property(property: 'last_page_url', type: 'string', example: 'http://localhost/api/v1/leave/history?page=1'),
                                new OA\Property(property: 'next_page_url', type: 'string', nullable: true, example: null),
                                new OA\Property(property: 'path', type: 'string', example: 'http://localhost/api/v1/leave/history'),
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
            new OA\Response(
                response: 401,
                description: 'Chưa đăng nhập (Unauthenticated)'
            ),
            new OA\Response(
                response: 403,
                description: 'Tài khoản của người dùng đã bị khóa hoặc ngừng hoạt động'
            ),
            new OA\Response(
                response: 500,
                description: 'Lỗi máy chủ không tải được lịch sử nghỉ phép'
            )
        ]
    )]
    public function history(LeaveHistoryRequest $request): JsonResponse
    {
        $filter = $request->getFilterOptions();
        $result = $this->leaveService->getLeaveHistory($request->user()->id, $filter);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Put(
        path: '/api/v1/leave/requests/{id}/cancel',
        summary: 'Hủy đơn xin nghỉ phép (UC-045)',
        description: 'Cho phép nhân viên hủy đơn xin nghỉ phép đang ở trạng thái chờ duyệt (pending).',
        tags: ['Leave'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'ID của đơn xin nghỉ phép (UUID)',
                schema: new OA\Schema(type: 'string', format: 'uuid')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Hủy yêu cầu nghỉ phép thành công',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Hủy yêu cầu nghỉ phép thành công.'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/LeaveRequest'),
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Không thể hủy do đơn đã được duyệt hoặc đã xử lý',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Không thể hủy yêu cầu đã được duyệt.'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Chưa đăng nhập (Unauthenticated)'),
            new OA\Response(response: 403, description: 'Không có quyền hủy đơn của người khác hoặc tài khoản bị khóa'),
            new OA\Response(response: 404, description: 'Không tìm thấy đơn xin nghỉ phép'),
            new OA\Response(response: 500, description: 'Không thể hủy do lỗi kết nối CSDL hoặc máy chủ'),
        ]
    )]
    public function cancel(string $id): JsonResponse
    {
        $result = $this->leaveService->cancelLeaveRequest(request()->user()->id, $id);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Get(
        path: '/api/v1/leave/requests',
        summary: 'Xem danh sách yêu cầu nghỉ phép của phòng ban (UC-046)',
        description: 'Cho phép Team Leader (Broker) hoặc Admin xem danh sách yêu cầu xin nghỉ phép của nhân viên trong phòng ban.',
        tags: ['Leave'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', required: false, description: 'Trang hiện tại (bắt đầu từ 1)', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, description: 'Số bản ghi trên mỗi trang', schema: new OA\Schema(type: 'integer', default: 10)),
            new OA\Parameter(name: 'sort_by', in: 'query', required: false, description: 'Trường dùng để sắp xếp', schema: new OA\Schema(type: 'string', enum: ['created_at', 'start_date', 'end_date'], default: 'created_at')),
            new OA\Parameter(name: 'direction', in: 'query', required: false, description: 'Chiều sắp xếp', schema: new OA\Schema(type: 'string', enum: ['asc', 'desc'], default: 'desc')),
            new OA\Parameter(name: 'filters[leave_type]', in: 'query', required: false, description: 'Lọc theo loại nghỉ phép', schema: new OA\Schema(type: 'string', enum: ['annual', 'unpaid', 'personal', 'maternity', 'business', 'compensatory'])),
            new OA\Parameter(name: 'filters[status]', in: 'query', required: false, description: 'Lọc theo trạng thái xử lý', schema: new OA\Schema(type: 'string', enum: ['pending', 'approved', 'rejected'])),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Tải danh sách yêu cầu nghỉ phép thành công',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Tải danh sách yêu cầu nghỉ phép thành công.'),
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
                                            new OA\Property(property: 'department', type: 'string', example: 'Phòng Kinh doanh'),
                                            new OA\Property(property: 'leave_type', type: 'string', example: 'annual'),
                                            new OA\Property(property: 'start_date', type: 'string', format: 'date', example: '2026-05-20'),
                                            new OA\Property(property: 'end_date', type: 'string', format: 'date', example: '2026-05-22'),
                                            new OA\Property(property: 'number_of_days', type: 'integer', example: 3),
                                            new OA\Property(property: 'reason', type: 'string', example: 'Có việc gia đình đột xuất'),
                                            new OA\Property(property: 'status', type: 'integer', example: \App\Modules\Leave\Models\Enums\RequestStatus::PENDING->value),
                                            new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2026-05-19T08:15:23+07:00'),
                                        ],
                                        type: 'object'
                                    )
                                ),
                                new OA\Property(property: 'first_page_url', type: 'string', example: 'http://localhost/api/v1/leave/requests?page=1'),
                                new OA\Property(property: 'from', type: 'integer', example: 1),
                                new OA\Property(property: 'last_page', type: 'integer', example: 1),
                                new OA\Property(property: 'last_page_url', type: 'string', example: 'http://localhost/api/v1/leave/requests?page=1'),
                                new OA\Property(property: 'next_page_url', type: 'string', nullable: true, example: null),
                                new OA\Property(property: 'path', type: 'string', example: 'http://localhost/api/v1/leave/requests'),
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
            new OA\Response(response: 403, description: 'Không có quyền truy cập dữ liệu nghỉ phép'),
            new OA\Response(response: 500, description: 'Không thể tải danh sách do lỗi kết nối CSDL hoặc máy chủ'),
        ]
    )]
    public function index(ViewLeaveRequestsRequest $request): JsonResponse
    {
        $filter = $request->getFilterOptions();
        $result = $this->leaveService->getDepartmentLeaveRequests($request->user()->id, $filter);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Put(
        path: '/api/v1/leave/requests/{id}/approve',
        summary: 'Phê duyệt yêu cầu nghỉ phép của nhân viên (UC-047)',
        description: 'Cho phép Team Leader (Broker) hoặc Admin phê duyệt một yêu cầu nghỉ phép đang chờ duyệt.',
        tags: ['Leave'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'ID của yêu cầu nghỉ phép (UUID)',
                schema: new OA\Schema(type: 'string', format: 'uuid', example: 'd3b07384-d113-4ec2-a5d6-c734b1234567')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Phê duyệt yêu cầu nghỉ phép thành công',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Duyệt đơn nghỉ phép thành công.'),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'id', type: 'string', format: 'uuid', example: 'd3b07384-d113-4ec2-a5d6-c734b1234567'),
                                new OA\Property(property: 'user_id', type: 'string', format: 'uuid', example: 'a1b2c3d4-e5f6-7a8b-9c0d-1e2f3a4b5c6d'),
                                new OA\Property(property: 'leave_type', type: 'string', example: 'annual'),
                                new OA\Property(property: 'start_date', type: 'string', format: 'date', example: '2026-05-20'),
                                new OA\Property(property: 'end_date', type: 'string', format: 'date', example: '2026-05-22'),
                                new OA\Property(property: 'reason', type: 'string', example: 'Nghỉ phép năm'),
                                new OA\Property(property: 'status', type: 'integer', example: \App\Modules\Leave\Models\Enums\RequestStatus::APPROVED->value),
                            ],
                            type: 'object'
                        )
                    ]
                )
            ),
            new OA\Response(response: 400, description: 'Yêu cầu không còn ở trạng thái chờ duyệt hoặc dữ liệu không hợp lệ'),
            new OA\Response(response: 401, description: 'Chưa đăng nhập (Unauthenticated)'),
            new OA\Response(response: 403, description: 'Không có quyền duyệt nghỉ phép hoặc tài khoản bị khóa'),
            new OA\Response(response: 404, description: 'Không tìm thấy yêu cầu nghỉ phép hoặc người dùng'),
            new OA\Response(response: 500, description: 'Không thể duyệt yêu cầu do lỗi máy chủ hoặc CSDL'),
        ]
    )]
    public function approve(string $id): JsonResponse
    {
        $result = $this->leaveService->approveLeaveRequest(request()->user()->id, $id);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Put(
        path: '/api/v1/leave/requests/{id}/reject',
        summary: 'Từ chối yêu cầu nghỉ phép của nhân viên (UC-048)',
        description: 'Cho phép Team Leader (Broker) hoặc Admin từ chối một yêu cầu nghỉ phép đang chờ duyệt.',
        tags: ['Leave'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'ID của yêu cầu nghỉ phép (UUID)',
                schema: new OA\Schema(type: 'string', format: 'uuid', example: 'd3b07384-d113-4ec2-a5d6-c734b1234567')
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            description: 'Lý do từ chối đơn',
            content: new OA\JsonContent(
                required: ['reason'],
                properties: [
                    new OA\Property(property: 'reason', type: 'string', maxLength: 1000, example: 'Dự án đang trong giai đoạn gấp, không thể duyệt phép dài ngày.')
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Từ chối yêu cầu nghỉ phép thành công',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Từ chối đơn nghỉ phép thành công.'),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'id', type: 'string', format: 'uuid', example: 'd3b07384-d113-4ec2-a5d6-c734b1234567'),
                                new OA\Property(property: 'user_id', type: 'string', format: 'uuid', example: 'a1b2c3d4-e5f6-7a8b-9c0d-1e2f3a4b5c6d'),
                                new OA\Property(property: 'leave_type', type: 'string', example: 'annual'),
                                new OA\Property(property: 'start_date', type: 'string', format: 'date', example: '2026-05-20'),
                                new OA\Property(property: 'end_date', type: 'string', format: 'date', example: '2026-05-22'),
                                new OA\Property(property: 'status', type: 'integer', example: \App\Modules\Leave\Models\Enums\RequestStatus::REJECTED->value),
                                new OA\Property(property: 'rejection_reason', type: 'string', example: 'Dự án đang trong giai đoạn gấp, không thể duyệt phép dài ngày.'),
                            ],
                            type: 'object'
                        )
                    ]
                )
            ),
            new OA\Response(response: 400, description: 'Yêu cầu không còn ở trạng thái chờ duyệt hoặc dữ liệu không hợp lệ'),
            new OA\Response(response: 401, description: 'Chưa đăng nhập (Unauthenticated)'),
            new OA\Response(response: 403, description: 'Không có quyền từ chối nghỉ phép hoặc tài khoản bị khóa'),
            new OA\Response(response: 404, description: 'Không tìm thấy yêu cầu nghỉ phép hoặc người dùng'),
            new OA\Response(response: 422, description: 'Lỗi validate dữ liệu (thiếu reason)'),
            new OA\Response(response: 500, description: 'Không thể từ chối yêu cầu do lỗi máy chủ hoặc CSDL'),
        ]
    )]
    public function reject(RejectLeaveRequest $request, string $id): JsonResponse
    {
        $result = $this->leaveService->rejectLeaveRequest(
            userId: $request->user()->id,
            leaveRequestId: $id,
            reason: $request->validated('reason')
        );

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }
}
