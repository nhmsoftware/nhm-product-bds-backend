<?php

namespace App\Modules\Consultation\Http\Controllers;

use App\Core\Controller\BaseController;
use App\Modules\Consultation\DTO\SubmitConsultationDTO;
use App\Modules\Consultation\Http\Requests\SubmitConsultationRequest;
use App\Modules\Consultation\Interfaces\ConsultationMessageServiceInterface;
use App\Modules\Consultation\Interfaces\ConsultationSettingServiceInterface;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

final class ConsultationController extends BaseController
{
    /**
     * ConsultationController constructor.
     *
     * @param ConsultationSettingServiceInterface $consultationSettingService
     * @param ConsultationMessageServiceInterface $consultationMessageService
     */
    public function __construct(
        private readonly ConsultationSettingServiceInterface $consultationSettingService,
        private readonly ConsultationMessageServiceInterface $consultationMessageService
    ) {
    }

    #[OA\Get(
        path: '/api/v1/public/consultation/setting',
        description: 'Lấy cấu hình thông tin liên hệ tư vấn hệ thống bao gồm hotline, yêu cầu gọi lại và form gửi tin nhắn tư vấn.',
        summary: 'Xem cấu hình thông tin liên hệ tư vấn (UC-024)',
        tags: ['Public Consultation'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Thành công',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Tải thông tin liên hệ tư vấn thành công.'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/ConsultationSetting'),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Không có dữ liệu liên hệ (A1)',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Thông tin liên hệ đang được cập nhật.'),
                    ]
                )
            ),
            new OA\Response(
                response: 500,
                description: 'Lỗi tải dữ liệu (A2)',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Không thể tải thông tin liên hệ. Vui lòng thử lại.'),
                    ]
                )
            )
        ]
    )]
    public function show(): JsonResponse
    {
        $result = $this->consultationSettingService->getActiveSetting();

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Post(
        path: '/api/v1/public/consultation/submit',
        description: 'Người dùng gửi tin nhắn yêu cầu tư vấn bất động sản thông qua form liên hệ.',
        summary: 'Gửi yêu cầu tư vấn (UC-026)',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['full_name', 'phone'],
                properties: [
                    new OA\Property(property: 'full_name', type: 'string', example: 'Nguyễn Văn A'),
                    new OA\Property(property: 'phone', type: 'string', example: '0912345678'),
                    new OA\Property(property: 'email', type: 'string', format: 'email', nullable: true, example: 'customer@example.com'),
                    new OA\Property(property: 'project_id', type: 'string', format: 'uuid', nullable: true, example: 'd3b07384-d113-49c2-a558-e244247a88ca'),
                    new OA\Property(property: 'project_name', type: 'string', nullable: true, example: 'Dự án Thủ Thiêm'),
                    new OA\Property(property: 'content', type: 'string', nullable: true, example: 'Tôi cần tư vấn thêm.'),
                ]
            )
        ),
        tags: ['Public Consultation'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Thành công',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Gửi yêu cầu tư vấn thành công.'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/ConsultationMessage'),
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Dữ liệu không hợp lệ (A1, A2, A3)',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Dữ liệu không hợp lệ.'),
                        new OA\Property(property: 'errors', type: 'object'),
                    ]
                )
            ),
            new OA\Response(
                response: 500,
                description: 'Lỗi gửi yêu cầu tư vấn (A4)',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Không thể gửi yêu cầu tư vấn. Vui lòng thử lại.'),
                    ]
                )
            )
        ]
    )]
    public function submit(SubmitConsultationRequest $request): JsonResponse
    {
        $dto = SubmitConsultationDTO::fromRequest($request);
        $result = $this->consultationMessageService->submitMessage($dto);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }
}
