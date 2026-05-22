<?php

namespace App\Modules\Learning\Http\Controllers;

use App\Core\Controller\BaseController;
use App\Modules\Learning\DTO\AdminViewCoursesDTO;
use App\Modules\Learning\DTO\AdminCreateCourseDTO;
use App\Modules\Learning\DTO\AdminUpdateCourseDTO;
use App\Modules\Learning\DTO\AdminCreateLessonDTO;
use App\Modules\Learning\DTO\AdminUpdateLessonDTO;
use App\Modules\Learning\DTO\AdminCreateQuizDTO;
use App\Modules\Learning\DTO\AdminUpdateQuizDTO;
use App\Modules\Learning\Http\Requests\AdminViewCoursesRequest;
use App\Modules\Learning\Http\Requests\AdminCreateCourseRequest;
use App\Modules\Learning\Http\Requests\AdminUpdateCourseRequest;
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
        summary: 'Tạo khóa học mới (UC-069)',
        tags: ['Learning Admin'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'title', type: 'string'),
                    new OA\Property(property: 'description', type: 'string', nullable: true),
                    new OA\Property(property: 'thumbnail', type: 'string', nullable: true),
                    new OA\Property(property: 'is_required', type: 'boolean', default: true),
                    new OA\Property(property: 'department', type: 'string', nullable: true),
                    new OA\Property(property: 'job_position', type: 'string', nullable: true),
                    new OA\Property(property: 'order', type: 'integer', default: 0),
                    new OA\Property(property: 'is_active', type: 'boolean', default: true),
                    new OA\Property(property: 'has_certificate', type: 'boolean', default: true)
                ]
            )
        ),
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
        summary: 'Cập nhật thông tin khóa học (UC-069)',
        tags: ['Learning Admin'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'title', type: 'string', nullable: true),
                    new OA\Property(property: 'description', type: 'string', nullable: true),
                    new OA\Property(property: 'thumbnail', type: 'string', nullable: true),
                    new OA\Property(property: 'is_required', type: 'boolean', nullable: true),
                    new OA\Property(property: 'department', type: 'string', nullable: true),
                    new OA\Property(property: 'job_position', type: 'string', nullable: true),
                    new OA\Property(property: 'order', type: 'integer', nullable: true),
                    new OA\Property(property: 'is_active', type: 'boolean', nullable: true),
                    new OA\Property(property: 'has_certificate', type: 'boolean', nullable: true)
                ]
            )
        ),
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
                    new OA\Property(property: 'duration_minutes', type: 'integer'),
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
        tags: ['Learning Admin'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'title', type: 'string', nullable: true),
                    new OA\Property(property: 'content', type: 'string', nullable: true),
                    new OA\Property(property: 'video_url', type: 'string', nullable: true),
                    new OA\Property(property: 'duration_minutes', type: 'integer', nullable: true),
                    new OA\Property(property: 'order', type: 'integer', nullable: true),
                    new OA\Property(property: 'is_active', type: 'boolean', nullable: true),
                    new OA\Property(property: 'attachments', type: 'array', items: new OA\Items(type: 'object'), nullable: true)
                ]
            )
        ),
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
        summary: 'Xác nhận hoàn thành onboarding cho nhân viên (UC-069)',
        tags: ['Learning Admin'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'courseId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'userId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Xác nhận hoàn thành onboarding thành công'),
            new OA\Response(response: 404, description: 'Không tìm thấy nhân viên hoặc khóa học')
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
}
