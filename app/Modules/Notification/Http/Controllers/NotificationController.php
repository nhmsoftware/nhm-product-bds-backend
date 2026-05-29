<?php

declare(strict_types=1);

namespace App\Modules\Notification\Http\Controllers;

use App\Core\Controller\BaseController;
use App\Modules\Notification\Interfaces\NotificationServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

#[OA\Tag(
    name: 'Notification',
    description: 'Quản lý thông báo cá nhân của người dùng (UC-130, UC-131)'
)]
final class NotificationController extends BaseController
{
    /**
     * Khởi tạo Controller và inject NotificationService qua Interface.
     *
     * @param NotificationServiceInterface $notificationService
     */
    public function __construct(
        private readonly NotificationServiceInterface $notificationService
    ) {
    }

    #[OA\Get(
        path: '/api/v1/notifications',
        summary: 'Xem danh sách thông báo cá nhân (UC-130)',
        description: 'Cho phép người dùng đã đăng nhập xem danh sách thông báo cá nhân liên quan đến tài khoản, công việc, khóa học, KPI, bảng hàng, đơn nghỉ phép, tuyển dụng và các hoạt động nội bộ. Danh sách được sắp xếp theo thứ tự mới nhất, thông báo chưa đọc hiển thị trên cùng.',
        tags: ['Notification'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', required: false, description: 'Trang hiện tại (bắt đầu từ 1)', schema: new OA\Schema(type: 'integer', default: 1, minimum: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, description: 'Số thông báo trên mỗi trang (tối đa 50)', schema: new OA\Schema(type: 'integer', default: 20, minimum: 1, maximum: 50)),
            new OA\Parameter(name: 'is_read', in: 'query', required: false, description: 'Lọc theo trạng thái đọc: true = đã đọc, false = chưa đọc. Bỏ trống để lấy tất cả.', schema: new OA\Schema(type: 'boolean', nullable: true)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Tải danh sách thông báo thành công',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Tải danh sách thông báo thành công.'),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'unread_count', type: 'integer', description: 'Tổng số thông báo chưa đọc', example: 3),
                                new OA\Property(
                                    property: 'notifications',
                                    type: 'object',
                                    properties: [
                                        new OA\Property(property: 'current_page', type: 'integer', example: 1),
                                        new OA\Property(
                                            property: 'data',
                                            type: 'array',
                                            items: new OA\Items(ref: '#/components/schemas/Notification')
                                        ),
                                        new OA\Property(property: 'total', type: 'integer', example: 15),
                                        new OA\Property(property: 'per_page', type: 'integer', example: 20),
                                        new OA\Property(property: 'last_page', type: 'integer', example: 1),
                                        new OA\Property(property: 'from', type: 'integer', example: 1),
                                        new OA\Property(property: 'to', type: 'integer', example: 15),
                                        new OA\Property(property: 'next_page_url', type: 'string', nullable: true, example: null),
                                        new OA\Property(property: 'prev_page_url', type: 'string', nullable: true, example: null),
                                    ]
                                ),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Chưa đăng nhập (Unauthenticated)'),
            new OA\Response(response: 403, description: 'Tài khoản đã bị khóa hoặc ngừng hoạt động'),
            new OA\Response(response: 500, description: 'Không thể tải thông báo do lỗi máy chủ'),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->query('per_page', 20), 50);
        $page    = max((int) $request->query('page', 1), 1);
        $isRead  = $request->has('is_read') ? filter_var($request->query('is_read'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) : null;

        $result = $this->notificationService->getNotifications(
            userId: $request->user()->id,
            perPage: $perPage,
            page: $page,
            isRead: $isRead,
        );

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Get(
        path: '/api/v1/notifications/{id}',
        summary: 'Xem chi tiết thông báo và tự động đánh dấu đã đọc (UC-131 – A5)',
        description: 'Lấy nội dung chi tiết của một thông báo và tự động đánh dấu trạng thái thông báo là Đã đọc (A5). Nếu thông báo đã được đọc trước đó, trạng thái được giữ nguyên (A1). Nếu thông báo không tồn tại, trả về lỗi 404 (A2).',
        tags: ['Notification'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'UUID của thông báo cần xem chi tiết',
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Tải chi tiết thông báo thành công, đã tự động đánh dấu đã đọc',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Tải chi tiết thông báo thành công.'),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'notification', ref: '#/components/schemas/Notification', description: 'Chi tiết thông báo (is_read luôn = true sau khi gọi API này)'),
                                new OA\Property(property: 'unread_count', type: 'integer', description: 'Tổng số thông báo chưa đọc hiện tại (sau khi đã cập nhật)', example: 2),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Chưa đăng nhập (Unauthenticated)'),
            new OA\Response(response: 403, description: 'Không có quyền xem thông báo này'),
            new OA\Response(response: 404, description: 'Thông báo không tồn tại hoặc đã bị xóa (A2)'),
            new OA\Response(response: 500, description: 'Không thể cập nhật trạng thái thông báo do lỗi máy chủ (A3)'),
        ]
    )]
    public function show(Request $request, string $id): JsonResponse
    {
        $result = $this->notificationService->getNotificationDetail(
            userId: $request->user()->id,
            notificationId: $id,
        );

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Put(
        path: '/api/v1/notifications/{id}/read',
        summary: 'Đánh dấu một thông báo là đã đọc (UC-131)',
        description: 'Cập nhật trạng thái đã đọc cho một thông báo cụ thể (Normal Flow – UC-131). Nếu thông báo đã đọc trước đó (A1), trạng thái được giữ nguyên. Nếu thông báo không tồn tại (A2), trả về lỗi 404.',
        tags: ['Notification'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'UUID của thông báo cần đánh dấu đã đọc',
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Đánh dấu đã đọc thành công',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Đã đánh dấu thông báo là đã đọc.'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/Notification'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Chưa đăng nhập (Unauthenticated)'),
            new OA\Response(response: 403, description: 'Không có quyền truy cập thông báo này'),
            new OA\Response(response: 404, description: 'Thông báo không tồn tại hoặc đã bị xóa'),
            new OA\Response(response: 500, description: 'Không thể cập nhật trạng thái thông báo do lỗi máy chủ'),
        ]
    )]
    public function markAsRead(Request $request, string $id): JsonResponse
    {
        $result = $this->notificationService->markAsRead(
            userId: $request->user()->id,
            notificationId: $id,
        );

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Put(
        path: '/api/v1/notifications/read-all',
        summary: 'Đánh dấu tất cả thông báo là đã đọc (UC-131 – A4)',
        description: 'Cập nhật trạng thái đã đọc cho toàn bộ thông báo chưa đọc của người dùng đang đăng nhập (A4 – UC-131). Số lượng thông báo được cập nhật sẽ được trả về để Frontend cập nhật badge.',
        tags: ['Notification'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Đánh dấu tất cả đã đọc thành công',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Đã đánh dấu tất cả thông báo là đã đọc.'),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'updated_count', type: 'integer', description: 'Số thông báo được cập nhật', example: 5),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Chưa đăng nhập (Unauthenticated)'),
            new OA\Response(response: 403, description: 'Tài khoản đã bị khóa hoặc ngừng hoạt động'),
            new OA\Response(response: 500, description: 'Không thể cập nhật trạng thái thông báo do lỗi máy chủ'),
        ]
    )]
    public function markAllAsRead(Request $request): JsonResponse
    {
        $result = $this->notificationService->markAllAsRead(
            userId: $request->user()->id,
        );

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }
}
