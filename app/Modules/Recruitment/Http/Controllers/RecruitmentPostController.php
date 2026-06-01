<?php

namespace App\Modules\Recruitment\Http\Controllers;

use App\Core\Controller\BaseController;
use App\Modules\Recruitment\DTO\CreateRecruitmentPostRequest;
use App\Modules\Recruitment\DTO\ListRecruitmentPostRequest;
use App\Modules\Recruitment\DTO\UpdateRecruitmentPostRequest;
use App\Modules\Recruitment\Interfaces\RecruitmentPostServiceInterface;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

#[OA\Tag(
    name: 'Recruitment Management',
    description: 'API Quản lý bài tuyển dụng dành cho Super Admin (UC-126)'
)]
class RecruitmentPostController extends BaseController
{
    public function __construct(
        private readonly RecruitmentPostServiceInterface $service
    ) {
    }

    #[OA\Get(
        path: '/api/v1/recruitment/posts',
        summary: 'Lấy danh sách bài tuyển dụng (UC-126)',
        security: [['bearerAuth' => []]],
        tags: ['Recruitment Management'],
        parameters: [
            new OA\Parameter(name: 'search', description: 'Từ khóa tìm kiếm (tiêu đề, vị trí, phòng ban...)', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'status', description: 'Lọc theo trạng thái (1: Đang hiển thị, 2: Đã ẩn)', in: 'query', required: false, schema: new OA\Schema(type: 'integer', enum: [1, 2])),
            new OA\Parameter(name: 'per_page', description: 'Số lượng trên 1 trang', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 15)),
            new OA\Parameter(name: 'page', description: 'Trang hiện tại', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Lấy danh sách thành công')
        ]
    )]
    public function index(ListRecruitmentPostRequest $request): JsonResponse
    {
        $result = $this->service->getList($request->validated());
        return $this->sendSuccess($result);
    }

    #[OA\Get(
        path: '/api/v1/recruitment/posts/{id}',
        summary: 'Xem chi tiết bài tuyển dụng (UC-126)',
        security: [['bearerAuth' => []]],
        tags: ['Recruitment Management'],
        parameters: [
            new OA\Parameter(name: 'id', description: 'ID bài tuyển dụng (UUID)', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Lấy chi tiết thành công'),
            new OA\Response(response: 404, description: 'Không tìm thấy bài tuyển dụng phù hợp.')
        ]
    )]
    public function show(string $id): JsonResponse
    {
        $result = $this->service->getDetail($id);
        return $this->sendSuccess($result);
    }

    #[OA\Post(
        path: '/api/v1/recruitment/posts',
        summary: 'Tạo bài tuyển dụng mới (UC-126)',
        security: [['bearerAuth' => []]],
        tags: ['Recruitment Management'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['title', 'branch_name', 'job_position', 'department', 'status'],
                properties: [
                    new OA\Property(property: 'title', type: 'string', example: 'Tuyển dụng Chuyên viên Kinh doanh'),
                    new OA\Property(property: 'image', type: 'string', example: 'https://example.com/image.jpg', nullable: true),
                    new OA\Property(property: 'branch_name', type: 'string', example: 'Chi nhánh Quận 1'),
                    new OA\Property(property: 'job_position', type: 'string', example: 'Chuyên viên Kinh doanh'),
                    new OA\Property(property: 'department', type: 'string', example: 'Phòng Kinh doanh'),
                    new OA\Property(property: 'short_description', type: 'string', example: 'Mô tả ngắn gọn về công việc', nullable: true),
                    new OA\Property(property: 'content', type: 'string', example: 'Chi tiết mô tả công việc...', nullable: true),
                    new OA\Property(property: 'job_description', type: 'string', example: 'Mô tả công việc cụ thể...', nullable: true),
                    new OA\Property(property: 'candidate_requirements', type: 'string', example: 'Yêu cầu ứng viên...', nullable: true),
                    new OA\Property(property: 'benefits', type: 'string', example: 'Quyền lợi được hưởng...', nullable: true),
                    new OA\Property(property: 'status', type: 'integer', description: '1: Đang hiển thị, 2: Đã ẩn', example: 1)
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Tạo bài tuyển dụng thành công.')
        ]
    )]
    public function store(CreateRecruitmentPostRequest $request): JsonResponse
    {
        $result = $this->service->create($request->validated());
        return $this->sendSuccess($result);
    }

    #[OA\Put(
        path: '/api/v1/recruitment/posts/{id}',
        summary: 'Cập nhật bài tuyển dụng (UC-126)',
        security: [['bearerAuth' => []]],
        tags: ['Recruitment Management'],
        parameters: [
            new OA\Parameter(name: 'id', description: 'ID bài tuyển dụng', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'title', type: 'string', example: 'Tuyển dụng Chuyên viên Kinh doanh (Update)'),
                    new OA\Property(property: 'image', type: 'string', example: 'https://example.com/image_new.jpg', nullable: true),
                    new OA\Property(property: 'branch_name', type: 'string', example: 'Chi nhánh Quận 1'),
                    new OA\Property(property: 'job_position', type: 'string', example: 'Chuyên viên Kinh doanh'),
                    new OA\Property(property: 'department', type: 'string', example: 'Phòng Kinh doanh'),
                    new OA\Property(property: 'job_description', type: 'string', example: 'Mô tả công việc cụ thể... (Update)', nullable: true),
                    new OA\Property(property: 'candidate_requirements', type: 'string', example: 'Yêu cầu ứng viên... (Update)', nullable: true),
                    new OA\Property(property: 'benefits', type: 'string', example: 'Quyền lợi được hưởng... (Update)', nullable: true),
                    new OA\Property(property: 'status', type: 'integer', example: 2)
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Cập nhật thành công'),
            new OA\Response(response: 404, description: 'Không tìm thấy bài tuyển dụng')
        ]
    )]
    public function update(UpdateRecruitmentPostRequest $request, string $id): JsonResponse
    {
        $result = $this->service->update($id, $request->validated());
        return $this->sendSuccess($result);
    }

    #[OA\Delete(
        path: '/api/v1/recruitment/posts/{id}',
        summary: 'Ẩn bài tuyển dụng (UC-129)',
        security: [['bearerAuth' => []]],
        tags: ['Recruitment Management'],
        parameters: [
            new OA\Parameter(name: 'id', description: 'ID bài tuyển dụng', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Ẩn bài tuyển dụng thành công'),
            new OA\Response(response: 404, description: 'Bài tuyển dụng không tồn tại')
        ]
    )]
    public function destroy(string $id): JsonResponse
    {
        $result = $this->service->delete($id);
        return $this->sendSuccess($result);
    }
}
