<?php

namespace App\Modules\ActivityEvidence\Http\Controllers;

use App\Core\Controller\BaseController;
use App\Modules\ActivityEvidence\DTO\UploadEvidenceDTO;
use App\Modules\ActivityEvidence\Http\Requests\UploadEvidenceRequest;
use App\Modules\ActivityEvidence\Interfaces\EvidenceServiceInterface;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

final class EvidenceController extends BaseController
{
    public function __construct(
        private readonly EvidenceServiceInterface $evidenceService
    ) {
    }

    #[OA\Post(
        path: '/api/v1/evidence/upload',
        description: 'Cho phép nhân viên tải lên ảnh minh chứng hoạt động sale bằng cách chụp ảnh hoặc chọn ảnh từ thiết bị.',
        summary: 'Tải ảnh minh chứng hoạt động (UC-040)',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['image'],
                    properties: [
                        new OA\Property(property: 'image', type: 'string', format: 'binary', description: 'Ảnh minh chứng cần tải lên'),
                    ]
                )
            )
        ),
        security: [['sanctum' => []]],
        tags: ['Activity Evidence'],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Tải minh chứng thành công',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Tải minh chứng thành công.'),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'url', type: 'string', example: '/storage/evidence/photo.png'),
                                new OA\Property(property: 'path', type: 'string', example: 'evidence/photo.png'),
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Lỗi xác thực dữ liệu đầu vào (A1, A2, A3)',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Định dạng file không hợp lệ.'),
                    ]
                )
            ),
            new OA\Response(
                response: 500,
                description: 'Lỗi máy chủ / Không thể tải minh chứng lên hệ thống (A4)',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Không thể tải minh chứng.'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Chưa đăng nhập (Unauthenticated)'),
            new OA\Response(response: 403, description: 'Tài khoản bị khóa hoặc không đủ quyền'),
        ]
    )]
    public function upload(UploadEvidenceRequest $request): JsonResponse
    {
        $dto = UploadEvidenceDTO::fromRequest($request, $request->user()->id);
        $result = $this->evidenceService->uploadEvidence($dto);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage(), 201);
    }
}
