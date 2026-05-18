<?php

namespace App\Modules\LegalVideo\Http\Controllers;

use App\Core\Controller\BaseController;
use App\Modules\LegalVideo\DTO\GetLegalVideoListDTO;
use App\Modules\LegalVideo\Http\Requests\GetLegalVideoListRequest;
use App\Modules\LegalVideo\Interfaces\LegalVideoServiceInterface;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

final class LegalVideoController extends BaseController
{
    public function __construct(
        private readonly LegalVideoServiceInterface $legalVideoService
    ) {
    }

    #[OA\Get(
        path: '/api/v1/legal-videos',
        description: 'Lấy danh sách video kiến thức và pháp lý bất động sản, hỗ trợ tìm kiếm nâng cao và lọc theo danh mục.',
        summary: 'Tìm kiếm và xem danh sách thư viện video pháp lý (UC-027, UC-028)',
        tags: ['LegalVideo'],
        parameters: [
            new OA\Parameter(
                name: 'category',
                in: 'query',
                description: 'Lọc theo danh mục: project_legal | contract | planning | transaction_process | other',
                required: false,
                schema: new OA\Schema(type: 'string', enum: ['project_legal', 'contract', 'planning', 'transaction_process', 'other'])
            ),
            new OA\Parameter(
                name: 'search',
                in: 'query',
                description: 'Từ khóa tìm kiếm theo tiêu đề, danh mục, mô tả ngắn hoặc từ khóa liên quan',
                required: false,
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'page',
                in: 'query',
                description: 'Trang hiện tại',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 1)
            ),
            new OA\Parameter(
                name: 'per_page',
                in: 'query',
                description: 'Số lượng mỗi trang',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 10)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Tải danh sách video thành công',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Tải danh sách video pháp lý thành công.'),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(
                                    property: 'list',
                                    type: 'array',
                                    items: new OA\Items(ref: '#/components/schemas/LegalVideo')
                                ),
                                new OA\Property(
                                    property: 'pagination',
                                    type: 'object',
                                    properties: [
                                        new OA\Property(property: 'total', type: 'integer', example: 50),
                                        new OA\Property(property: 'per_page', type: 'integer', example: 10),
                                        new OA\Property(property: 'current_page', type: 'integer', example: 1),
                                        new OA\Property(property: 'last_page', type: 'integer', example: 5),
                                    ]
                                ),
                                new OA\Property(
                                    property: 'categories',
                                    type: 'array',
                                    items: new OA\Items(type: 'object')
                                )
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Dữ liệu tìm kiếm không hợp lệ (khi không nhập từ khóa)',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Dữ liệu không hợp lệ.'),
                        new OA\Property(
                            property: 'errors',
                            type: 'object',
                            properties: [
                                new OA\Property(
                                    property: 'search',
                                    type: 'array',
                                    items: new OA\Items(type: 'string', example: 'Vui lòng nhập từ khóa tìm kiếm.')
                                )
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(response: 500, description: 'Lỗi hệ thống')
        ]
    )]
    public function index(GetLegalVideoListRequest $request): JsonResponse
    {
        $dto = GetLegalVideoListDTO::fromRequest($request);
        $result = $this->legalVideoService->getList($dto);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Get(
        path: '/api/v1/legal-videos/{idOrSlug}',
        description: 'Xem chi tiết video pháp lý và phát video theo ID hoặc Slug.',
        summary: 'Phát và xem chi tiết video pháp lý (UC-027, UC-029)',
        tags: ['LegalVideo'],
        parameters: [
            new OA\Parameter(
                name: 'idOrSlug',
                in: 'path',
                description: 'ID hoặc Slug của video pháp lý',
                required: true,
                schema: new OA\Schema(type: 'string')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Tải chi tiết video thành công',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Tải chi tiết video thành công.'),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'detail', ref: '#/components/schemas/LegalVideo')
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Không tìm thấy video (Video không tồn tại, đã bị xóa, hoặc hiện không khả dụng/bị ẩn)',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Video không tồn tại hoặc đã bị xóa.')
                    ]
                )
            ),
            new OA\Response(response: 500, description: 'Lỗi hệ thống')
        ]
    )]
    public function show(string $idOrSlug): JsonResponse
    {
        $result = $this->legalVideoService->getDetail($idOrSlug);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }
}
