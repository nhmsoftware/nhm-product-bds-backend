<?php

namespace App\Modules\Planning\Http\Controllers;

use App\Core\Controller\BaseController;
use App\Modules\Planning\DTO\PlanningListDTO;
use App\Modules\Planning\Http\Requests\GetPlanningListRequest;
use App\Modules\Planning\Interfaces\PlanningServiceInterface;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

final class PlanningController extends BaseController
{
    /**
     * PlanningController constructor.
     * 
     * @param PlanningServiceInterface $planningService
     */
    public function __construct(
        private readonly PlanningServiceInterface $planningService
    ) {
    }

    #[OA\Get(
        path: '/api/v1/public/plannings',
        description: 'Lấy danh sách thông tin quy hoạch bất động sản công khai theo khu vực.',
        summary: 'Xem danh sách quy hoạch công khai',
        tags: ['Public Planning'],
        parameters: [
            new OA\Parameter(name: 'search', description: 'Tìm kiếm khu vực', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'city', description: 'Lọc theo tỉnh/thành phố', in: 'query', schema: new OA\Schema(type: 'string', enum: [
                'Tất cả khu vực', 'TP. Hồ Chí Minh', 'Hà Nội', 'Đà Nẵng', 'Bình Dương', 'Đồng Nai', 'Khác'
            ])),
            new OA\Parameter(name: 'per_page', description: 'Số lượng item trên mỗi trang', in: 'query', schema: new OA\Schema(type: 'integer', default: 10)),
            new OA\Parameter(name: 'page', description: 'Trang hiện tại', in: 'query', schema: new OA\Schema(type: 'integer', default: 1)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Thành công',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Tải danh sách quy hoạch thành công.'),
                        new OA\Property(property: 'data', properties: [
                            new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Planning')),
                            new OA\Property(property: 'current_page', type: 'integer'),
                            new OA\Property(property: 'last_page', type: 'integer'),
                            new OA\Property(property: 'total', type: 'integer'),
                        ], type: 'object'),
                    ]
                )
            ),
            new OA\Response(response: 500, description: 'Lỗi hệ thống')
        ]
    )]
    public function index(GetPlanningListRequest $request): JsonResponse
    {
        $dto = PlanningListDTO::fromRequest($request);
        $result = $this->planningService->getPublicList($dto);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Get(
        path: '/api/v1/public/plannings/search',
        description: 'Tìm kiếm thông tin quy hoạch công khai theo tên khu vực, quận/huyện hoặc tỉnh/thành phố.',
        summary: 'Tìm kiếm quy hoạch',
        tags: ['Public Planning'],
        parameters: [
            new OA\Parameter(name: 'q', in: 'query', description: 'Từ khóa tìm kiếm', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'per_page', in: 'query', description: 'Số lượng item trên mỗi trang', schema: new OA\Schema(type: 'integer', default: 10)),
            new OA\Parameter(name: 'page', in: 'query', description: 'Trang hiện tại', schema: new OA\Schema(type: 'integer', default: 1)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Thành công',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Tìm thấy 5 kết quả phù hợp.'),
                        new OA\Property(property: 'data', properties: [
                            new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Planning')),
                            new OA\Property(property: 'current_page', type: 'integer'),
                            new OA\Property(property: 'last_page', type: 'integer'),
                            new OA\Property(property: 'total', type: 'integer'),
                        ], type: 'object'),
                    ]
                )
            ),
            new OA\Response(response: 422, description: 'Dữ liệu không hợp lệ (Thiếu từ khóa)'),
            new OA\Response(response: 500, description: 'Lỗi hệ thống')
        ]
    )]
    public function search(SearchPlanningRequest $request): JsonResponse
    {
        $result = $this->planningService->search(
            $request->query('q'),
            (int) $request->query('per_page', 10),
            (int) $request->query('page', 1)
        );

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Get(
        path: '/api/v1/public/plannings/cities',
        description: 'Lấy danh sách các tỉnh/thành phố hiện có dữ liệu quy hoạch để dùng cho bộ lọc.',
        summary: 'Lấy danh sách tỉnh/thành phố lọc',
        tags: ['Public Planning'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Thành công',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Tải danh sách tỉnh/thành phố thành công.'),
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'string', example: 'TP. Hồ Chí Minh')),
                    ]
                )
            ),
            new OA\Response(response: 500, description: 'Lỗi hệ thống')
        ]
    )]
    public function getCities(): JsonResponse
    {
        $result = $this->planningService->getFilterCities();

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Get(
        path: '/api/v1/plannings/{id}/download',
        description: 'Lấy link tải file PDF hồ sơ quy hoạch (Yêu cầu đăng nhập Customer).',
        summary: 'Tải PDF quy hoạch',
        security: [['bearerAuth' => []]],
        tags: ['Planning'],
        parameters: [
            new OA\Parameter(name: 'id', description: 'ID của quy hoạch (UUID)', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Thành công',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Tải PDF quy hoạch thành công.'),
                        new OA\Property(property: 'data', type: 'object', properties: [
                            new OA\Property(property: 'url', type: 'string', example: 'https://example.com/plan.pdf'),
                            new OA\Property(property: 'title', type: 'string', example: 'Quy hoạch Thủ Thiêm'),
                        ]),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Chưa đăng nhập'),
            new OA\Response(response: 404, description: 'Không tìm thấy file hoặc quy hoạch'),
            new OA\Response(response: 500, description: 'Lỗi hệ thống')
        ]
    )]
    public function download(string $id): JsonResponse
    {
        $result = $this->planningService->getDownloadLink($id);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Get(
        path: '/api/v1/public/plannings/{id}',
        description: 'Lấy thông tin chi tiết của một quy hoạch bất động sản công khai.',
        summary: 'Xem chi tiết quy hoạch công khai',
        tags: ['Public Planning'],
        parameters: [
            new OA\Parameter(name: 'id', description: 'ID của quy hoạch (UUID)', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Thành công',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Tải chi tiết quy hoạch thành công.'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/Planning'),
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Quy hoạch không tồn tại'),
            new OA\Response(response: 500, description: 'Lỗi hệ thống')
        ]
    )]
    public function show(string $id): JsonResponse
    {
        $result = $this->planningService->getDetail($id);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }
}
