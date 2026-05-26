<?php

declare(strict_types=1);

namespace App\Modules\Area\Http\Controllers;

use App\Core\Controller\BaseController;
use App\Modules\Area\Interfaces\AreaServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Admin Area & Lots', description: 'API quản lý khu đất và lô đất bất động sản (Admin)')]
class AdminAreaController extends BaseController
{
    public function __construct(
        private readonly AreaServiceInterface $areaService
    ) {}

    #[OA\Patch(
        path: '/api/v1/admin/lots/{id}/lock',
        summary: 'Khóa/Mở khóa lô đất (UC-089)',
        security: [['bearerAuth' => []]],
        tags: ['Admin Area & Lots'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'is_locked', type: 'boolean')
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Thành công')
        ]
    )]
    public function lockLot(Request $request, string $id): JsonResponse
    {
        $userId = (string) $request->user()->id;
        $isLocked = $request->boolean('is_locked', true);

        $result = $this->areaService->lockUnlockLot($userId, $id, $isLocked);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }
}
