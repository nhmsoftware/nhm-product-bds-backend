<?php

declare(strict_types=1);

namespace App\Modules\Area\Http\Controllers;

use App\Core\Controller\BaseController;
use App\Modules\Area\DTO\FilterLotDepositRequestDTO;
use App\Modules\Area\Interfaces\LotDepositRequestServiceInterface;
use App\Modules\Auth\Models\Enums\UserRole;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

final class AdminLotDepositRequestController extends BaseController
{
    public function __construct(
        private readonly LotDepositRequestServiceInterface $service
    ) {
    }

    #[OA\Get(
        path: '/api/v1/admin/deposit-requests',
        summary: 'Lấy danh sách yêu cầu đặt cọc (Admin) [UC-091]',
        security: [['bearerAuth' => []]],
        tags: ['Area'],
        parameters: [
            new OA\Parameter(name: 'status', description: 'Lọc theo trạng thái', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'project_id', description: 'Lọc theo dự án', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'employee_id', description: 'Lọc theo nhân viên', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'branch', description: 'Lọc theo chi nhánh', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'search', description: 'Tìm kiếm theo tên khách hàng, mã lô, v.v.', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'page', description: 'Trang hiện tại', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'per_page', description: 'Số bản ghi trên mỗi trang', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 15))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Danh sách yêu cầu đặt cọc.'),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        // Xử lý logic General Director chỉ xem chi nhánh của mình
        $user = $request->user();
        $roleName = $user->role?->name;
        $branch = $request->input('branch');

        // Nếu không phải super_admin thì ép branch theo area của User (nếu role là Giám đốc/CEO)
        if ($roleName !== 'super_admin') {
            $branch = $user->area;
            $request->merge(['branch' => $branch]);
        }

        $dto = FilterLotDepositRequestDTO::fromRequest($request);
        $result = $this->service->adminGetList($dto);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Get(
        path: '/api/v1/admin/deposit-requests/{id}',
        summary: 'Xem chi tiết yêu cầu đặt cọc (Admin) [UC-091]',
        security: [['bearerAuth' => []]],
        tags: ['Area'],
        parameters: [
            new OA\Parameter(name: 'id', description: 'ID yêu cầu đặt cọc', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Chi tiết yêu cầu đặt cọc.'),
            new OA\Response(response: 404, description: 'Không tìm thấy.')
        ]
    )]
    public function show(string $id): JsonResponse
    {
        $result = $this->service->adminGetDetail($id);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Patch(
        path: '/api/v1/admin/deposit-requests/{id}/approve',
        summary: 'Duyệt yêu cầu đặt cọc (Admin) [UC-091]',
        security: [['bearerAuth' => []]],
        tags: ['Area'],
        parameters: [
            new OA\Parameter(name: 'id', description: 'ID yêu cầu', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Đã duyệt yêu cầu đặt cọc.'),
            new OA\Response(response: 400, description: 'Lỗi nghiệp vụ.')
        ]
    )]
    public function approve(Request $request, string $id): JsonResponse
    {
        $result = $this->service->adminApprove($id, $request->user());

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Patch(
        path: '/api/v1/admin/deposit-requests/{id}/reject',
        summary: 'Từ chối yêu cầu đặt cọc (Admin) [UC-091, UC-093]',
        security: [['bearerAuth' => []]],
        tags: ['Area'],
        parameters: [
            new OA\Parameter(name: 'id', description: 'ID yêu cầu', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['reject_reason'],
                properties: [
                    new OA\Property(property: 'reject_reason', type: 'string', description: 'Lý do từ chối', example: 'Lô đất đã được khách khác đặt cọc')
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Đã từ chối yêu cầu đặt cọc.'),
            new OA\Response(response: 400, description: 'Lỗi nghiệp vụ.')
        ]
    )]
    public function reject(Request $request, string $id): JsonResponse
    {
        $reason = $request->input('reject_reason');
        if (empty($reason)) {
            return $this->sendError('Vui lòng nhập lý do từ chối.', 400);
        }

        $result = $this->service->adminReject($id, $request->user(), $reason);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Patch(
        path: '/api/v1/admin/deposit-requests/{id}/confirm-transaction',
        summary: 'Xác nhận giao dịch thành công (Admin) [UC-091]',
        security: [['bearerAuth' => []]],
        tags: ['Area'],
        parameters: [
            new OA\Parameter(name: 'id', description: 'ID yêu cầu', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Xác nhận giao dịch thành công.'),
            new OA\Response(response: 400, description: 'Lỗi nghiệp vụ.')
        ]
    )]
    public function confirmTransaction(Request $request, string $id): JsonResponse
    {
        $result = $this->service->adminConfirmTransaction($id, $request->user());

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }
}
