<?php

namespace App\Modules\Learning\Http\Controllers;

use App\Core\Controller\BaseController;
use App\Modules\Learning\DTO\AdminViewCoursesDTO;
use App\Modules\Learning\DTO\AdminCreateCourseDTO;
use App\Modules\Learning\DTO\AdminUpdateCourseDTO;
use App\Modules\Learning\DTO\AdminUpdateCourseStatusDTO;
use App\Modules\Learning\DTO\AdminCreateCourseQuizDTO;
use App\Modules\Learning\DTO\AdminUpdateCourseQuizDTO;
use App\Modules\Learning\DTO\AdminCreateLessonDTO;
use App\Modules\Learning\DTO\AdminUpdateLessonDTO;
use App\Modules\Learning\DTO\AdminCreateQuizDTO;
use App\Modules\Learning\DTO\AdminUpdateQuizDTO;
use App\Modules\Learning\Http\Requests\AdminViewCoursesRequest;
use App\Modules\Learning\Http\Requests\AdminCreateCourseRequest;
use App\Modules\Learning\Http\Requests\AdminUpdateCourseRequest;
use App\Modules\Learning\Http\Requests\AdminUpdateCourseStatusRequest;
use App\Modules\Learning\Http\Requests\AdminCreateCourseQuizRequest;
use App\Modules\Learning\Http\Requests\AdminUpdateCourseQuizRequest;
use App\Modules\Learning\Http\Requests\AdminCreateLessonRequest;
use App\Modules\Learning\Http\Requests\AdminUpdateLessonRequest;
use App\Modules\Learning\Http\Requests\AdminCreateQuizRequest;
use App\Modules\Learning\Http\Requests\AdminUpdateQuizRequest;
use App\Modules\Learning\Interfaces\LearningServiceInterface;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

/**
 * Class LearningAdminController
 *
 * Điều phối các hoạt động quản lý khóa học LMS dành cho Super Admin (UC-069).
 *
 * @package App\Modules\Learning\Http\Controllers
 */
final class LearningAdminController extends BaseController
{
    public function __construct(
        private readonly LearningServiceInterface $learningService
    ) {
    }

    #[OA\Get(
        path: '/api/v1/learning/admin/courses',
        summary: 'Tải danh sách khóa học kèm tìm kiếm và lọc dành cho Admin (UC-069)',
        tags: ['Learning Admin'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'search', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'is_active', in: 'query', required: false, schema: new OA\Schema(type: 'boolean')),
            new OA\Parameter(name: 'is_required', in: 'query', required: false, schema: new OA\Schema(type: 'boolean')),
            new OA\Parameter(name: 'department', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'job_position', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 10)),
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Tải danh sách khóa học thành công'),
            new OA\Response(response: 403, description: 'Không có quyền truy cập')
        ]
    )]
    public function getCourses(AdminViewCoursesRequest $request): JsonResponse
    {
        $dto = AdminViewCoursesDTO::fromRequest($request);
        $result = $this->learningService->adminGetCourses($dto, $request->user()->id);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Get(
        path: '/api/v1/learning/admin/courses/{id}',
        summary: 'Xem chi tiết khóa học cho Admin bao gồm toàn bộ bài học và quiz (UC-069)',
        tags: ['Learning Admin'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Tải chi tiết khóa học thành công'),
            new OA\Response(response: 404, description: 'Không tìm thấy khóa học')
        ]
    )]
    public function getCourseDetails(string $id, AdminViewCoursesRequest $request): JsonResponse
    {
        $result = $this->learningService->adminGetCourseDetails($id, $request->user()->id);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Post(
        path: '/api/v1/learning/admin/courses',
        summary: 'Tạo khóa học mới (UC-070)',
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['title', 'lessons'],
                properties: [
                    new OA\Property(property: 'title', type: 'string', example: 'Khóa học thiết kế Figma'),
                    new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Hướng dẫn thiết kế UI/UX'),
                    new OA\Property(property: 'thumbnail', type: 'string', nullable: true, example: 'https://example.com/thumbnail.png'),
                    new OA\Property(property: 'is_required', type: 'boolean', default: true, example: false),
                    new OA\Property(property: 'department', type: 'string', nullable: true, example: 'Design'),
                    new OA\Property(property: 'job_position', type: 'string', nullable: true, example: 'UI/UX Designer'),
                    new OA\Property(property: 'order', type: 'integer', default: 0, example: 5),
                    new OA\Property(property: 'is_active', type: 'boolean', default: true, example: true),
                    new OA\Property(property: 'has_certificate', type: 'boolean', default: true, example: true),
                    new OA\Property(
                        property: 'lessons',
                        type: 'array',
                        items: new OA\Items(
                            type: 'object',
                            required: ['title', 'duration_seconds'],
                            properties: [
                                new OA\Property(property: 'title', type: 'string', example: 'Bài học Figma 1'),
                                new OA\Property(property: 'content', type: 'string', nullable: true, example: 'Nội dung Figma'),
                                new OA\Property(property: 'video_url', type: 'string', nullable: true, example: 'https://example.com/figma1.mp4'),
                                new OA\Property(property: 'duration_seconds', type: 'integer', example: 30),
                                new OA\Property(property: 'order', type: 'integer', example: 1),
                                new OA\Property(property: 'is_active', type: 'boolean', example: true),
                                new OA\Property(
                                    property: 'attachments',
                                    type: 'array',
                                    items: new OA\Items(
                                        type: 'object',
                                        required: ['name', 'url'],
                                        properties: [
                                            new OA\Property(property: 'name', type: 'string', example: 'Tài liệu hướng dẫn'),
                                            new OA\Property(property: 'url', type: 'string', example: 'https://example.com/doc1.pdf')
                                        ]
                                    ),
                                    nullable: true
                                ),
                                new OA\Property(
                                    property: 'quizzes',
                                    type: 'array',
                                    items: new OA\Items(
                                        type: 'object',
                                        required: ['question', 'options', 'correct_option'],
                                        properties: [
                                            new OA\Property(property: 'question', type: 'string', example: 'Figma là gì?'),
                                            new OA\Property(
                                                property: 'options',
                                                type: 'array',
                                                items: new OA\Items(type: 'string'),
                                                example: ['Công cụ design', 'Công cụ soạn thảo', 'Trình duyệt']
                                            ),
                                            new OA\Property(property: 'correct_option', type: 'integer', example: 0)
                                        ]
                                    ),
                                    nullable: true
                                )
                            ]
                        )
                    )
                ]
            )
        ),
        tags: ['Learning Admin'],
        responses: [
            new OA\Response(response: 200, description: 'Tạo khóa học thành công'),
            new OA\Response(response: 422, description: 'Dữ liệu không hợp lệ')
        ]
    )]
    public function createCourse(AdminCreateCourseRequest $request): JsonResponse
    {
        $dto = AdminCreateCourseDTO::fromRequest($request);
        $result = $this->learningService->adminCreateCourse($dto, $request->user()->id);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Put(
        path: '/api/v1/learning/admin/courses/{id}',
        summary: 'Cập nhật thông tin khóa học (UC-071)',
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['title', 'lessons'],
                properties: [
                    new OA\Property(property: 'title', type: 'string', example: 'Khóa học thiết kế Figma - Cập nhật'),
                    new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Hướng dẫn thiết kế UI/UX nâng cao'),
                    new OA\Property(property: 'thumbnail', type: 'string', nullable: true, example: 'https://example.com/thumbnail-updated.png'),
                    new OA\Property(property: 'is_required', type: 'boolean', nullable: true, example: false),
                    new OA\Property(property: 'department', type: 'string', nullable: true, example: 'Design'),
                    new OA\Property(property: 'job_position', type: 'string', nullable: true, example: 'Senior UI/UX Designer'),
                    new OA\Property(property: 'order', type: 'integer', nullable: true, example: 5),
                    new OA\Property(property: 'is_active', type: 'boolean', nullable: true, example: true),
                    new OA\Property(property: 'has_certificate', type: 'boolean', nullable: true, example: true),
                    new OA\Property(
                        property: 'lessons',
                        type: 'array',
                        items: new OA\Items(
                            required: ['title', 'duration_seconds'],
                            properties: [
                                new OA\Property(property: 'id', type: 'string', format: 'uuid', nullable: true, example: 'b0f80a44-df42-493e-9081-305128362678'),
                                new OA\Property(property: 'title', type: 'string', example: 'Bài học Figma 1 updated'),
                                new OA\Property(property: 'content', type: 'string', nullable: true, example: 'Nội dung Figma nâng cao'),
                                new OA\Property(property: 'video_url', type: 'string', nullable: true, example: 'https://example.com/figma1_new.mp4'),
                                new OA\Property(property: 'duration_seconds', type: 'integer', example: 35),
                                new OA\Property(property: 'order', type: 'integer', example: 1),
                                new OA\Property(property: 'is_active', type: 'boolean', example: true),
                                new OA\Property(
                                    property: 'attachments',
                                    type: 'array',
                                    items: new OA\Items(
                                        required: ['name', 'url'],
                                        properties: [
                                            new OA\Property(property: 'name', type: 'string', example: 'Tài liệu hướng dẫn mới'),
                                            new OA\Property(property: 'url', type: 'string', example: 'https://example.com/doc1_new.pdf')
                                        ],
                                        type: 'object'
                                    ),
                                    nullable: true
                                ),
                                new OA\Property(
                                    property: 'quizzes',
                                    type: 'array',
                                    items: new OA\Items(
                                        required: ['question', 'options', 'correct_option'],
                                        properties: [
                                            new OA\Property(property: 'id', type: 'string', format: 'uuid', nullable: true, example: 'c0a80e12-4217-47b2-8c90-215106a7cd5b'),
                                            new OA\Property(property: 'question', type: 'string', example: 'Figma là gì?'),
                                            new OA\Property(
                                                property: 'options',
                                                type: 'array',
                                                items: new OA\Items(type: 'string'),
                                                example: ['Công cụ design chuyên nghiệp', 'Công cụ soạn thảo', 'Trình duyệt']
                                            ),
                                            new OA\Property(property: 'correct_option', type: 'integer', example: 0)
                                        ],
                                        type: 'object'
                                    ),
                                    nullable: true
                                )
                            ],
                            type: 'object'
                        )
                    )
                ]
            )
        ),
        tags: ['Learning Admin'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Cập nhật khóa học thành công'),
            new OA\Response(response: 404, description: 'Không tìm thấy khóa học')
        ]
    )]
    public function updateCourse(string $id, AdminUpdateCourseRequest $request): JsonResponse
    {
        $dto = AdminUpdateCourseDTO::fromRequest($request);
        $result = $this->learningService->adminUpdateCourse($id, $dto, $request->user()->id);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Patch(
        path: '/api/v1/learning/admin/courses/{id}/status',
        summary: 'Cập nhật trạng thái khóa học (UC-072)',
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['is_active'],
                properties: [
                    new OA\Property(property: 'is_active', type: 'boolean', example: false)
                ]
            )
        ),
        tags: ['Learning Admin'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Cập nhật trạng thái khóa học thành công'),
            new OA\Response(response: 404, description: 'Không tìm thấy khóa học'),
            new OA\Response(response: 422, description: 'Dữ liệu không hợp lệ')
        ]
    )]
    public function updateCourseStatus(string $id, AdminUpdateCourseStatusRequest $request): JsonResponse
    {
        $dto = AdminUpdateCourseStatusDTO::fromRequest($request);
        $result = $this->learningService->adminUpdateCourseStatus($id, $dto, $request->user()->id);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Post(
        path: '/api/v1/learning/admin/courses/{id}/quiz',
        summary: 'Tạo bài quiz cho khóa học (UC-073)',
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['title', 'passing_score', 'questions'],
                properties: [
                    new OA\Property(property: 'title', type: 'string', example: 'Quiz văn hóa doanh nghiệp'),
                    new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Bài kiểm tra cuối khóa học'),
                    new OA\Property(property: 'passing_score', type: 'number', example: 80.00),
                    new OA\Property(
                        property: 'questions',
                        type: 'array',
                        items: new OA\Items(
                            type: 'object',
                            required: ['question', 'options', 'correct_option'],
                            properties: [
                                new OA\Property(property: 'question', type: 'string', example: 'Giá trị cốt lõi đầu tiên là gì?'),
                                new OA\Property(
                                    property: 'options',
                                    type: 'array',
                                    items: new OA\Items(type: 'string'),
                                    example: ['Khách hàng là trọng tâm', 'Tự do sáng tạo', 'Kỷ luật tối đa']
                                ),
                                new OA\Property(property: 'correct_option', type: 'integer', example: 0)
                            ]
                        )
                    )
                ]
            )
        ),
        tags: ['Learning Admin'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Tạo bài quiz thành công'),
            new OA\Response(response: 400, description: 'Yêu cầu không hợp lệ'),
            new OA\Response(response: 404, description: 'Không tìm thấy khóa học'),
            new OA\Response(response: 422, description: 'Dữ liệu không hợp lệ')
        ]
    )]
    public function createCourseQuiz(string $id, AdminCreateCourseQuizRequest $request): JsonResponse
    {
        $dto = AdminCreateCourseQuizDTO::fromRequest($request);
        $result = $this->learningService->adminCreateCourseQuiz($id, $dto, $request->user()->id);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Put(
        path: '/api/v1/learning/admin/courses/{id}/quiz',
        summary: 'Cập nhật bài quiz cho khóa học (UC-074)',
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['title', 'passing_score', 'questions'],
                properties: [
                    new OA\Property(property: 'title', type: 'string', example: 'Quiz văn hóa doanh nghiệp cập nhật'),
                    new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Bài kiểm tra cuối khóa học đã cập nhật'),
                    new OA\Property(property: 'passing_score', type: 'number', example: 80.00),
                    new OA\Property(
                        property: 'questions',
                        type: 'array',
                        items: new OA\Items(
                            type: 'object',
                            required: ['question', 'options', 'correct_option'],
                            properties: [
                                new OA\Property(property: 'id', type: 'string', format: 'uuid', nullable: true, example: 'd3b07384-d113-4ec5-a3d6-444455556666'),
                                new OA\Property(property: 'question', type: 'string', example: 'Giá trị cốt lõi đầu tiên là gì?'),
                                new OA\Property(
                                    property: 'options',
                                    type: 'array',
                                    items: new OA\Items(type: 'string'),
                                    example: ['Khách hàng là trọng tâm', 'Tự do sáng tạo', 'Kỷ luật tối đa']
                                ),
                                new OA\Property(property: 'correct_option', type: 'integer', example: 0)
                            ]
                        )
                    )
                ]
            )
        ),
        tags: ['Learning Admin'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Cập nhật bài quiz thành công'),
            new OA\Response(response: 400, description: 'Yêu cầu không hợp lệ'),
            new OA\Response(response: 404, description: 'Quiz hoặc khóa học không tồn tại'),
            new OA\Response(response: 422, description: 'Dữ liệu không hợp lệ')
        ]
    )]
    public function updateCourseQuiz(string $id, AdminUpdateCourseQuizRequest $request): JsonResponse
    {
        $dto = AdminUpdateCourseQuizDTO::fromRequest($request);
        $result = $this->learningService->adminUpdateCourseQuiz($id, $dto, $request->user()->id);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Delete(
        path: '/api/v1/learning/admin/courses/{id}/quiz',
        summary: 'Xóa bài quiz của khóa học (UC-075)',
        tags: ['Learning Admin'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Xóa quiz thành công'),
            new OA\Response(response: 400, description: 'Yêu cầu không hợp lệ hoặc quiz đã có nhân viên làm bài'),
            new OA\Response(response: 404, description: 'Quiz hoặc khóa học không tồn tại')
        ]
    )]
    public function deleteCourseQuiz(string $id, \Illuminate\Http\Request $request): JsonResponse
    {
        $result = $this->learningService->adminDeleteCourseQuiz($id, $request->user()->id);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Delete(
        path: '/api/v1/learning/admin/courses/{id}',
        summary: 'Xóa khóa học (UC-069)',
        tags: ['Learning Admin'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Xóa khóa học thành công'),
            new OA\Response(response: 404, description: 'Không tìm thấy khóa học')
        ]
    )]
    public function deleteCourse(string $id, AdminViewCoursesRequest $request): JsonResponse
    {
        $result = $this->learningService->adminDeleteCourse($id, $request->user()->id);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Post(
        path: '/api/v1/learning/admin/lessons',
        summary: 'Tạo bài học mới (UC-069)',
        tags: ['Learning Admin'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'course_id', type: 'string', format: 'uuid'),
                    new OA\Property(property: 'title', type: 'string'),
                    new OA\Property(property: 'content', type: 'string', nullable: true),
                    new OA\Property(property: 'video_url', type: 'string', nullable: true),
                    new OA\Property(property: 'duration_seconds', type: 'integer'),
                    new OA\Property(property: 'order', type: 'integer', default: 0),
                    new OA\Property(property: 'is_active', type: 'boolean', default: true),
                    new OA\Property(property: 'attachments', type: 'array', items: new OA\Items(type: 'object'))
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Tạo bài học thành công'),
            new OA\Response(response: 404, description: 'Không tìm thấy khóa học')
        ]
    )]
    public function createLesson(AdminCreateLessonRequest $request): JsonResponse
    {
        $dto = AdminCreateLessonDTO::fromRequest($request);
        $result = $this->learningService->adminCreateLesson($dto, $request->user()->id);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Put(
        path: '/api/v1/learning/admin/lessons/{id}',
        summary: 'Cập nhật thông tin bài học (UC-069)',
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'title', type: 'string', nullable: true),
                    new OA\Property(property: 'content', type: 'string', nullable: true),
                    new OA\Property(property: 'video_url', type: 'string', nullable: true),
                    new OA\Property(property: 'duration_seconds', type: 'integer', nullable: true),
                    new OA\Property(property: 'order', type: 'integer', nullable: true),
                    new OA\Property(property: 'is_active', type: 'boolean', nullable: true),
                    new OA\Property(property: 'attachments', type: 'array', items: new OA\Items(type: 'object'), nullable: true)
                ]
            )
        ),
        tags: ['Learning Admin'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Cập nhật bài học thành công'),
            new OA\Response(response: 404, description: 'Không tìm thấy bài học')
        ]
    )]
    public function updateLesson(string $id, AdminUpdateLessonRequest $request): JsonResponse
    {
        $dto = AdminUpdateLessonDTO::fromRequest($request);
        $result = $this->learningService->adminUpdateLesson($id, $dto, $request->user()->id);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Delete(
        path: '/api/v1/learning/admin/lessons/{id}',
        summary: 'Xóa bài học (UC-069)',
        tags: ['Learning Admin'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Xóa bài học thành công'),
            new OA\Response(response: 404, description: 'Bài học không tồn tại')
        ]
    )]
    public function deleteLesson(string $id, AdminViewCoursesRequest $request): JsonResponse
    {
        $result = $this->learningService->adminDeleteLesson($id, $request->user()->id);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Post(
        path: '/api/v1/learning/admin/quizzes',
        summary: 'Tạo câu hỏi quiz kiểm tra bài học mới (UC-069)',
        tags: ['Learning Admin'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'lesson_id', type: 'string', format: 'uuid'),
                    new OA\Property(property: 'question', type: 'string'),
                    new OA\Property(property: 'options', type: 'array', items: new OA\Items(type: 'string')),
                    new OA\Property(property: 'correct_option', type: 'integer')
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Tạo câu hỏi quiz thành công'),
            new OA\Response(response: 404, description: 'Bài học không tồn tại')
        ]
    )]
    public function createQuiz(AdminCreateQuizRequest $request): JsonResponse
    {
        $dto = AdminCreateQuizDTO::fromRequest($request);
        $result = $this->learningService->adminCreateQuiz($dto, $request->user()->id);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Put(
        path: '/api/v1/learning/admin/quizzes/{id}',
        summary: 'Cập nhật thông tin câu hỏi quiz (UC-069)',
        tags: ['Learning Admin'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'question', type: 'string', nullable: true),
                    new OA\Property(property: 'options', type: 'array', items: new OA\Items(type: 'string'), nullable: true),
                    new OA\Property(property: 'correct_option', type: 'integer', nullable: true)
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Cập nhật câu hỏi quiz thành công'),
            new OA\Response(response: 404, description: 'Không tìm thấy câu hỏi quiz')
        ]
    )]
    public function updateQuiz(string $id, AdminUpdateQuizRequest $request): JsonResponse
    {
        $dto = AdminUpdateQuizDTO::fromRequest($request);
        $result = $this->learningService->adminUpdateQuiz($id, $dto, $request->user()->id);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Delete(
        path: '/api/v1/learning/admin/quizzes/{id}',
        summary: 'Xóa câu hỏi quiz (UC-069)',
        tags: ['Learning Admin'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Xóa câu hỏi quiz thành công'),
            new OA\Response(response: 404, description: 'Không tìm thấy câu hỏi quiz')
        ]
    )]
    public function deleteQuiz(string $id, AdminViewCoursesRequest $request): JsonResponse
    {
        $result = $this->learningService->adminDeleteQuiz($id, $request->user()->id);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Post(
        path: '/api/v1/learning/admin/courses/{courseId}/enrollments/{userId}/complete',
        summary: 'Xác nhận hoàn thành onboarding cho nhân viên (UC-076)',
        tags: ['Learning Admin'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'courseId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'userId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Xác nhận hoàn thành onboarding thành công'),
            new OA\Response(response: 404, description: 'Không tìm thấy nhân viên hoặc khóa học'),
            new OA\Response(response: 400, description: 'Nhân viên chưa đạt yêu cầu hoặc đã hoàn thành trước đó')
        ]
    )]
    public function confirmOnboarding(string $courseId, string $userId, AdminViewCoursesRequest $request): JsonResponse
    {
        $result = $this->learningService->adminConfirmOnboarding($courseId, $userId, $request->user()->id);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Get(
        path: '/api/v1/learning/admin/onboarding',
        summary: 'Tải danh sách tiến độ onboarding của nhân viên (UC-076)',
        tags: ['Learning Admin'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'department', in: 'query', required: false, schema: new OA\Schema(type: 'string'), description: 'Lọc theo phòng ban'),
            new OA\Parameter(name: 'status', in: 'query', required: false, schema: new OA\Schema(type: 'integer'), description: 'Lọc theo trạng thái (1: Chưa bắt đầu, 2: Đang học, 3: Hoàn thành)'),
            new OA\Parameter(name: 'quiz_score', in: 'query', required: false, schema: new OA\Schema(type: 'number', format: 'float'), description: 'Lọc theo điểm quiz')
        ],
        responses: [
            new OA\Response(response: 200, description: 'Tải danh sách onboarding thành công'),
            new OA\Response(response: 403, description: 'Không có quyền thực hiện chức năng này')
        ]
    )]
    public function getOnboardingList(\Illuminate\Http\Request $request): JsonResponse
    {
        $filters = $request->only(['department', 'status', 'quiz_score']);
        $result = $this->learningService->adminGetOnboardingList($filters, $request->user()->id);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Get(
        path: '/api/v1/learning/admin/courses/{courseId}/enrollments/{userId}',
        summary: 'Tải chi tiết tiến độ onboarding của một nhân viên (UC-076)',
        tags: ['Learning Admin'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'courseId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'userId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Tải thông tin chi tiết onboarding thành công'),
            new OA\Response(response: 404, description: 'Không tìm thấy thông tin tài khoản hoặc khóa học')
        ]
    )]
    public function getOnboardingDetail(string $courseId, string $userId, \Illuminate\Http\Request $request): JsonResponse
    {
        $result = $this->learningService->adminGetOnboardingDetail($courseId, $userId, $request->user()->id);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }
}
