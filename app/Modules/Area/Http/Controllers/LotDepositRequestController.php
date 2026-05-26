<?php

declare(strict_types=1);

namespace App\Modules\Area\Http\Controllers;

use App\Core\Controller\BaseController;
use App\Modules\Area\DTO\CreateLotDepositRequestDTO;
use App\Modules\Area\Http\Requests\CreateLotDepositRequestRequest;
use App\Modules\Area\Interfaces\LotDepositRequestServiceInterface;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

final class LotDepositRequestController extends BaseController
{
    public function __construct(
        private readonly LotDepositRequestServiceInterface $service
    ) {
    }

    #[OA\Post(
        path: '/api/lots/{lot}/deposit-requests',
        summary: 'Tạo yêu cầu đặt cọc lô đất [UC-083]',
        security: [['bearerAuth' => []]],
        tags: ['Area'],
        parameters: [
            new OA\Parameter(
                name: 'lot',
                description: 'ID lô đất',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'reason', type: 'string', description: 'Lý do đặt cọc', example: 'Khách cọc sớm 50 triệu')
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Yêu cầu đặt cọc đã được gửi thành công.'),
            new OA\Response(response: 400, description: 'Lỗi nghiệp vụ (lô đất đã bán, đang có yêu cầu khác...).'),
            new OA\Response(response: 404, description: 'Lô đất không tồn tại.')
        ]
    )]
    public function store(CreateLotDepositRequestRequest $request, string $lot): JsonResponse
    {
        $dto = CreateLotDepositRequestDTO::fromRequest($request, $lot);
        $result = $this->service->create($dto);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage(), $result->getCode());
    }
}
