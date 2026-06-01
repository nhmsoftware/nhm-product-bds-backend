<?php

namespace App\Modules\Learning\Http\Controllers;

use App\Core\Controller\BaseController;
use App\Modules\Learning\DTO\ViewCoursesDTO;
use App\Modules\Learning\Http\Requests\ViewCourseDetailsRequest;
use App\Modules\Learning\Http\Requests\ViewCoursesRequest;
use App\Modules\Learning\Interfaces\LearningServiceInterface;
use App\Modules\Learning\Http\Requests\ViewLessonDetailsRequest;
use App\Modules\Learning\Http\Requests\UpdateLessonProgressRequest;
use App\Modules\Learning\Http\Requests\SubmitQuizRequest;
use App\Modules\Learning\Http\Requests\SaveQuizDraftRequest;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

/**
 * Class LearningController
 *
 * Controller điều phối các hoạt động học tập và khóa học bắt buộc của nhân viên.
 *
 * @package App\Modules\Learning\Http\Controllers
 */
final class LearningController extends BaseController
{
    /**
     * Khởi tạo Controller và inject LearningService qua Interface.
     *
     * @param LearningServiceInterface $learningService
     */
    public function __construct(
        private readonly LearningServiceInterface $learningService
    ) {
    }

    #[OA\Get(
        path: '/api/v1/learning/courses',
        summary: 'Xem khóa học bắt buộc hiện tại của nhân viên (UC-053)',
        description: 'Lấy thông tin khóa học bắt buộc được phân bổ theo phòng ban hoặc vị trí công việc của nhân viên đang đăng nhập kèm theo quy tắc học tập và tiến độ.',
        tags: ['Learning'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Tải khóa học bắt buộc thành công',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Tải khóa học bắt buộc thành công.'),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(
                                    property: 'course',
                                    type: 'object',
                                    properties: [
                                        new OA\Property(property: 'id', type: 'string', format: 'uuid', example: 'd3b07384-d113-4ec2-a5d6-c734b1234567', description: 'ID của khóa học'),
                                        new OA\Property(property: 'title', type: 'string', example: 'Nền tảng Kinh doanh Bất động sản', description: 'Tiêu đề khóa học'),
                                        new OA\Property(property: 'label', type: 'string', example: 'QUY TRÌNH HỌC TỰ CNTR', description: 'Nhãn khóa học'),
                                        new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Hoàn thành tuần tự các bài giảng dưới đây để nắm vững quy trình bán hàng chuẩn. Bạn không thể bỏ qua bài học.', description: 'Mô tả khóa học'),
                                        new OA\Property(property: 'thumbnailUrl', type: 'string', nullable: true, example: 'https://cdn.example.com/courses/bds-foundation.jpg', description: 'Đường dẫn ảnh thu nhỏ khóa học'),
                                        new OA\Property(property: 'isMandatory', type: 'boolean', example: true, description: 'Khóa học có bắt buộc hay không'),
                                        new OA\Property(
                                            property: 'learningRule',
                                            type: 'object',
                                            description: 'Quy tắc học tập',
                                            properties: [
                                                new OA\Property(property: 'type', type: 'string', example: 'sequential', description: 'Loại quy tắc học (sequential: tuần tự)'),
                                                new OA\Property(property: 'canSkipLesson', type: 'boolean', example: false, description: 'Có thể bỏ qua bài học không'),
                                                new OA\Property(property: 'requireWatchFullVideo', type: 'boolean', example: true, description: 'Yêu cầu xem hết video không'),
                                                new OA\Property(property: 'autoTrackProgress', type: 'boolean', example: true, description: 'Tự động ghi nhận tiến trình học tập')
                                            ]
                                        ),
                                        new OA\Property(
                                            property: 'progress',
                                            type: 'object',
                                            description: 'Tiến độ học tập của nhân viên',
                                            properties: [
                                                new OA\Property(property: 'status', type: 'string', example: 'in_progress', description: 'Trạng thái tiến độ (not_started, in_progress, completed)'),
                                                new OA\Property(property: 'percent', type: 'integer', example: 15, description: 'Phần trăm hoàn thành khóa học'),
                                                new OA\Property(property: 'completedLessons', type: 'integer', example: 0, description: 'Số bài học đã hoàn thành'),
                                                new OA\Property(property: 'totalLessons', type: 'integer', example: 3, description: 'Tổng số bài học trong khóa học'),
                                                new OA\Property(property: 'currentLessonId', type: 'string', format: 'uuid', nullable: true, example: 'f87a8b9c-d0e1-4f2a-b3c4-d5e6f7a8b9c0', description: 'ID bài học hiện tại đang học')
                                            ]
                                        ),
                                        new OA\Property(
                                            property: 'quiz',
                                            type: 'object',
                                            description: 'Thông tin bài thi trắc nghiệm/tự luận của khóa học',
                                            properties: [
                                                new OA\Property(property: 'hasQuiz', type: 'boolean', example: true, description: 'Khóa học có bài thi không'),
                                                new OA\Property(property: 'status', type: 'string', example: 'grading', description: 'Trạng thái bài thi (not_started, grading, failed, passed)'),
                                                new OA\Property(property: 'actionText', type: 'string', example: 'Đang chấm bài', description: 'Nhãn hành động hiển thị cho bài thi'),
                                                new OA\Property(property: 'isPassed', type: 'boolean', example: false, description: 'Đã thi đạt chưa'),
                                                new OA\Property(property: 'lastScore', type: 'number', format: 'float', nullable: true, example: null, description: 'Điểm số bài thi gần nhất'),
                                                new OA\Property(property: 'passingScore', type: 'integer', example: 8, description: 'Điểm đạt yêu cầu'),
                                                new OA\Property(property: 'canStart', type: 'boolean', example: true, description: 'Có đủ điều kiện để bắt đầu thi (hoàn thành hết video)')
                                            ]
                                        ),
                                        new OA\Property(property: 'canAccessPremiumLearning', type: 'boolean', example: false, description: 'Có quyền truy cập kho học liệu cao cấp không'),
                                        new OA\Property(
                                            property: 'notice',
                                            type: 'object',
                                            description: 'Thông báo/Lưu ý học tập',
                                            properties: [
                                                new OA\Property(property: 'type', type: 'string', example: 'warning', description: 'Loại thông báo'),
                                                new OA\Property(property: 'message', type: 'string', example: 'Bạn cần xem hết thời lượng video trước khi chuyển sang bài tiếp theo. Hệ thống sẽ tự động ghi nhận tiến độ.', description: 'Nội dung thông báo')
                                            ]
                                        ),
                                        new OA\Property(
                                            property: 'lessons',
                                            type: 'array',
                                            description: 'Danh sách bài học trong khóa học',
                                            items: new OA\Items(
                                                properties: [
                                                    new OA\Property(property: 'id', type: 'string', format: 'uuid', example: 'f87a8b9c-d0e1-4f2a-b3c4-d5e6f7a8b9c0', description: 'ID bài học'),
                                                    new OA\Property(property: 'order', type: 'integer', example: 1, description: 'Thứ tự bài học'),
                                                    new OA\Property(property: 'title', type: 'string', example: 'Bài 1: Tổng quan về quy trình bán hàng', description: 'Tiêu đề bài học'),
                                                    new OA\Property(property: 'durationSeconds', type: 'integer', example: 600, description: 'Thời lượng video bài học tính bằng giây'),
                                                    new OA\Property(property: 'status', type: 'string', example: 'learning', description: 'Trạng thái bài học (completed, learning, locked)'),
                                                    new OA\Property(property: 'progressPercent', type: 'integer', example: 15, description: 'Tiến độ xem video của bài học (%)'),
                                                    new OA\Property(property: 'isLocked', type: 'boolean', example: false, description: 'Bài học có bị khóa không'),
                                                    new OA\Property(property: 'canContinue', type: 'boolean', example: true, description: 'Có thể tiếp tục học bài học này không'),
                                                    new OA\Property(property: 'actionText', type: 'string', example: 'Tiếp tục', description: 'Nhãn hành động hiển thị trên giao diện')
                                                ],
                                                type: 'object'
                                            )
                                        )
                                    ]
                                )
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Chưa đăng nhập (Unauthenticated)'
            ),
            new OA\Response(
                response: 403,
                description: 'Tài khoản của nhân viên đã bị khóa hoặc ngừng hoạt động'
            ),
            new OA\Response(
                response: 500,
                description: 'Không thể tải khóa học bắt buộc do lỗi hệ thống'
            )
        ]
    )]
    public function index(ViewCoursesRequest $request): JsonResponse
    {
        $dto = ViewCoursesDTO::fromRequest($request, $request->user()->id);
        $result = $this->learningService->getMandatoryCourses($dto);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Get(
        path: '/api/v1/learning/courses/{id}',
        summary: 'Xem chi tiết thông tin khóa học bắt buộc và danh sách bài học (UC-053)',
        description: 'Tải thông tin chi tiết của một khóa học bắt buộc, danh sách bài học kèm thời lượng video, tiến độ khóa học và trạng thái từng bài học.',
        tags: ['Learning'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'ID của khóa học (UUID)',
                schema: new OA\Schema(type: 'string', format: 'uuid')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Tải chi tiết khóa học thành công',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Tải thông tin khóa học thành công.'),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'string', format: 'uuid', example: 'd3b07384-d113-4ec2-a5d6-c734b1234567'),
                                new OA\Property(property: 'title', type: 'string', example: 'Khóa đào tạo văn hóa doanh nghiệp'),
                                new OA\Property(property: 'thumbnail', type: 'string', nullable: true, example: 'https://bds-app.s3.amazonaws.com/thumbnails/culture.jpg'),
                                new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Giới thiệu về giá trị cốt lõi và sứ mệnh của công ty.'),
                                new OA\Property(property: 'progress_percent', type: 'number', format: 'float', example: 0.00),
                                new OA\Property(property: 'status', type: 'integer', example: \App\Modules\Learning\Models\Enums\CourseEnrollmentStatus::IN_PROGRESS->value),
                                new OA\Property(
                                    property: 'lessons',
                                    type: 'array',
                                    items: new OA\Items(
                                        properties: [
                                            new OA\Property(property: 'id', type: 'string', format: 'uuid', example: 'f87a8b9c-d0e1-4f2a-b3c4-d5e6f7a8b9c0'),
                                            new OA\Property(property: 'title', type: 'string', example: 'Bài 1: Tổng quan về doanh nghiệp'),
                                            new OA\Property(property: 'content', type: 'string', nullable: true, example: 'Nội dung chi tiết bài học...'),
                                            new OA\Property(property: 'video_url', type: 'string', nullable: true, example: 'https://bds-app.s3.amazonaws.com/videos/lesson1.mp4'),
                                            new OA\Property(property: 'duration_seconds', type: 'integer', example: 15),
                                            new OA\Property(property: 'order', type: 'integer', example: 1),
                                            new OA\Property(property: 'status', type: 'integer', example: \App\Modules\Learning\Models\Enums\LessonStatus::LEARNING->value, description: 'Trạng thái bài học: 1 (completed), 2 (learning), 3 (locked)'),
                                            new OA\Property(property: 'status_label', type: 'string', example: 'Đang học')
                                        ],
                                        type: 'object'
                                    )
                                )
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Chưa đăng nhập (Unauthenticated)'
            ),
            new OA\Response(
                response: 403,
                description: 'Tài khoản của nhân viên đã bị khóa hoặc ngừng hoạt động'
            ),
            new OA\Response(
                response: 404,
                description: 'Không tìm thấy khóa học bắt buộc'
            ),
            new OA\Response(
                response: 500,
                description: 'Không thể tải chi tiết khóa học do lỗi hệ thống'
            )
        ]
    )]
    public function show(ViewCourseDetailsRequest $request, string $id): JsonResponse
    {
        $result = $this->learningService->getCourseDetails($id, $request->user()->id);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Get(
        path: '/api/v1/learning/lessons/{id}',
        description: 'Tải thông tin chi tiết của bài học bao gồm video đào tạo, mô tả bài học, trạng thái bài học và danh sách tài liệu đính kèm.',
        summary: 'Xem chi tiết thông tin bài học (UC-054)',
        security: [['sanctum' => []]],
        tags: ['Learning'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'ID của bài học (UUID)',
                schema: new OA\Schema(type: 'string', format: 'uuid')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Tải thông tin bài học thành công',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Tải thông tin bài học thành công.'),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'id', type: 'string', format: 'uuid', example: 'f87a8b9c-d0e1-4f2a-b3c4-d5e6f7a8b9c0'),
                                new OA\Property(property: 'course_id', type: 'string', format: 'uuid', example: 'd3b07384-d113-4ec2-a5d6-c734b1234567'),
                                new OA\Property(property: 'title', type: 'string', example: 'Bài 1: Tổng quan về doanh nghiệp'),
                                new OA\Property(property: 'content', type: 'string', nullable: true, example: 'Nội dung chi tiết bài học...', description: 'Nội dung chi tiết bài học'),
                                new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Nội dung chi tiết bài học...', description: 'Mô tả bài học'),
                                new OA\Property(property: 'video_url', type: 'string', nullable: true, example: 'https://bds-app.s3.amazonaws.com/videos/lesson1.mp4', description: 'Đường dẫn video bài học'),
                                new OA\Property(property: 'duration_seconds', type: 'integer', example: 15, description: 'Thời lượng video tính bằng giây'),
                                new OA\Property(property: 'order', type: 'integer', example: 1, description: 'Thứ tự bài học'),
                                new OA\Property(property: 'status', type: 'string', example: 'learning', description: 'Trạng thái bài học: completed, learning, locked'),
                                new OA\Property(property: 'status_label', type: 'string', example: 'Đang học', description: 'Trạng thái bài học/Bài thi (Đang học, Xem lại, Làm bài kiểm tra, Chưa đạt, Đang chấm bài)'),
                                new OA\Property(
                                    property: 'attachments',
                                    type: 'array',
                                    description: 'Danh sách tài liệu đính kèm',
                                    items: new OA\Items(
                                        properties: [
                                            new OA\Property(property: 'type', type: 'string', example: 'pdf'),
                                            new OA\Property(property: 'url', type: 'string', example: 'https://example.com/file.pdf'),
                                            new OA\Property(property: 'name', type: 'string', example: 'report.pdf')
                                        ],
                                        type: 'object'
                                    )
                                ),
                                new OA\Property(property: 'attachment_message', type: 'string', nullable: true, example: 'Không có tài liệu đính kèm.', description: 'Thông báo nếu không có tài liệu'),
                                new OA\Property(property: 'unlock_condition', type: 'string', example: 'Hoàn thành bài học này để mở khóa bài tiếp theo: Bài học số 2', description: 'Thông báo điều kiện mở khóa')
                            ],
                            type: 'object'
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Chưa đăng nhập (Unauthenticated)'
            ),
            new OA\Response(
                response: 403,
                description: 'Tài khoản của nhân viên đã bị khóa hoặc ngừng hoạt động, hoặc bài học chưa được mở khóa, hoặc nhân viên chưa tham gia khóa học',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Vui lòng hoàn thành bài học trước để mở khóa.')
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Bài học không tồn tại',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Bài học không tồn tại.')
                    ]
                )
            ),
            new OA\Response(
                response: 500,
                description: 'Không thể tải thông tin bài học'
            )
        ]
    )]
    public function showLesson(ViewLessonDetailsRequest $request, string $id): JsonResponse
    {
        $result = $this->learningService->getLessonDetails($id, $request->user()->id);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Post(
        path: '/api/v1/learning/lessons/{id}/progress',
        description: 'Cập nhật thời lượng xem video của bài học hiện tại. Nếu nhân viên xem đủ thời lượng yêu cầu, trạng thái bài học được cập nhật thành "Hoàn thành" và mở khóa bài tiếp theo.',
        summary: 'Cập nhật tiến độ xem video bài học và mở khóa (UC-055)',
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['watch_time_seconds'],
                properties: [
                    new OA\Property(property: 'watch_time_seconds', type: 'integer', example: 600, description: 'Thời lượng xem hiện tại tính bằng giây')
                ]
            )
        ),
        tags: ['Learning'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'ID của bài học (UUID)',
                schema: new OA\Schema(type: 'string', format: 'uuid')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Cập nhật tiến độ xem video thành công hoặc lưu tiến độ xem hiện tại',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Cập nhật tiến độ xem video thành công.'),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'lesson_id', type: 'string', format: 'uuid', example: 'f87a8b9c-d0e1-4f2a-b3c4-d5e6f7a8b9c0'),
                                new OA\Property(property: 'current_watch_seconds', type: 'integer', example: 600),
                                new OA\Property(property: 'is_completed', type: 'boolean', example: true, description: 'Trạng thái hoàn thành bài học'),
                                new OA\Property(property: 'course_progress_percent', type: 'number', format: 'float', example: 33.33),
                                new OA\Property(property: 'course_status', type: 'string', example: 'in_progress'),
                                new OA\Property(property: 'next_lesson_id', type: 'string', format: 'uuid', nullable: true, example: 'a1b2c3d4-e5f6-7a8b-9c0d-1e2f3a4b5c6d'),
                                new OA\Property(property: 'unlock_condition', type: 'string', example: 'Hoàn thành bài học này để mở khóa bài tiếp theo: Bài học số 2')
                            ],
                            type: 'object'
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Video hiện không khả dụng',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Video hiện không khả dụng.')
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Chưa đăng nhập (Unauthenticated)'
            ),
            new OA\Response(
                response: 403,
                description: 'Tài khoản nhân viên bị khóa, hoặc chưa tham gia khóa học',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Bạn chưa tham gia khóa học này.')
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Bài học hoặc khóa học không tồn tại',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Bài học không tồn tại.')
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Dữ liệu đầu vào không hợp lệ',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Dữ liệu không hợp lệ.'),
                        new OA\Property(
                            property: 'errors',
                            properties: [
                                new OA\Property(
                                    property: 'watch_time_seconds',
                                    type: 'array',
                                    items: new OA\Items(type: 'string', example: 'Vui lòng cung cấp thời lượng đã xem video.')
                                )
                            ],
                            type: 'object'
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 500,
                description: 'Không thể cập nhật tiến độ học tập'
            )
        ]
    )]
    public function updateProgress(UpdateLessonProgressRequest $request, string $id): JsonResponse
    {
        $result = $this->learningService->updateLessonProgress(
            $id,
            (int) $request->input('watch_time_seconds'),
            $request->user()->id
        );

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Get(
        path: '/api/v1/learning/courses/{id}/quiz',
        description: 'Tải danh sách các câu hỏi trắc nghiệm của khóa học để nhân viên bắt đầu làm bài. Danh sách câu hỏi không chứa kết quả đúng.',
        summary: 'Lấy danh sách câu hỏi kiểm tra khóa học (UC-056)',
        security: [['sanctum' => []]],
        tags: ['Learning'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'ID của khóa học (UUID)',
                schema: new OA\Schema(type: 'string', format: 'uuid')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Tải danh sách câu hỏi thành công',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Tải danh sách câu hỏi kiểm tra thành công.'),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'lesson_id', type: 'string', format: 'uuid', example: 'f87a8b9c-d0e1-4f2a-b3c4-d5e6f7a8b9c0'),
                                new OA\Property(property: 'lesson_title', type: 'string', example: 'Tổng quan thị trường BĐS Cao cấp'),
                                new OA\Property(property: 'quiz_title', type: 'string', example: 'Bài kiểm tra kiến thức'),
                                new OA\Property(property: 'time_limit_minutes', type: 'integer', example: 45),
                                new OA\Property(
                                    property: 'questions',
                                    type: 'array',
                                    items: new OA\Items(
                                        properties: [
                                            new OA\Property(property: 'id', type: 'string', format: 'uuid', example: 'e5f6g7h8-i9j0-k1l2-m3n4-o5p6q7r8s9t0'),
                                            new OA\Property(property: 'type', type: 'string', example: 'multiple_choice'),
                                            new OA\Property(property: 'order', type: 'integer', example: 1),
                                            new OA\Property(property: 'title', type: 'string', nullable: true, example: 'Câu 1: Quy hoạch phân khu'),
                                            new OA\Property(property: 'question', type: 'string', example: 'Giá trị cốt lõi đầu tiên của công ty là gì?'),
                                            new OA\Property(property: 'image_url', type: 'string', nullable: true, example: 'https://example.com/img.png'),
                                            new OA\Property(
                                                property: 'options',
                                                type: 'array',
                                                items: new OA\Items(
                                                    properties: [
                                                        new OA\Property(property: 'value', type: 'integer', example: 0),
                                                        new OA\Property(property: 'label', type: 'string', example: 'Đất thương mại dịch vụ')
                                                    ],
                                                    type: 'object'
                                                ),
                                                example: [
                                                    ['value' => 0, 'label' => 'Đất thương mại dịch vụ'],
                                                    ['value' => 1, 'label' => 'Đất ở đô thị hỗn hợp']
                                                ]
                                            ),
                                            new OA\Property(property: 'placeholder', type: 'string', nullable: true, example: 'Nhập câu trả lời của bạn tại đây...'),
                                            new OA\Property(property: 'draft_selected_option', type: 'integer', nullable: true, example: 0),
                                            new OA\Property(property: 'draft_essay_answer', type: 'string', nullable: true, example: 'Câu trả lời tự luận')
                                        ],
                                        type: 'object'
                                    )
                                )
                            ],
                            type: 'object'
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Chưa đăng nhập (Unauthenticated)'
            ),
            new OA\Response(
                response: 403,
                description: 'Tài khoản nhân viên bị khóa, chưa hoàn thành bài học, hoặc chưa tham gia khóa học',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Bạn cần hoàn thành bài học trước khi làm quiz.')
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Khóa học không tồn tại hoặc bài quiz không khả dụng',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Bài quiz không khả dụng.')
                    ]
                )
            ),
            new OA\Response(
                response: 500,
                description: 'Không thể tải thông tin bài quiz'
            )
        ]
    )]
    public function getQuiz(ViewCourseDetailsRequest $request, string $id): JsonResponse
    {
        $result = $this->learningService->getCourseQuiz($id, $request->user()->id);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Post(
        path: '/api/v1/learning/courses/{id}/quiz/submit',
        description: 'Chấm điểm bài thi và lưu lịch sử làm bài kiểm tra của nhân viên.',
        summary: 'Nộp bài kiểm tra trắc nghiệm của khóa học (UC-056)',
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['answers'],
                properties: [
                    new OA\Property(
                        property: 'answers',
                        type: 'array',
                        items: new OA\Items(
                            properties: [
                                new OA\Property(property: 'quiz_id', type: 'string', format: 'uuid', example: 'e5f6g7h8-i9j0-k1l2-m3n4-o5p6q7r8s9t0'),
                                new OA\Property(property: 'selected_option', type: 'integer', nullable: true, example: 0),
                                new OA\Property(property: 'essay_answer', type: 'string', nullable: true, example: 'Nội dung tự luận')
                            ],
                            type: 'object'
                        )
                    ),
                    new OA\Property(property: 'is_timeout', type: 'boolean', example: false, description: 'True nếu tự động nộp bài do hết giờ')
                ]
            )
        ),
        tags: ['Learning'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'ID của khóa học (UUID)',
                schema: new OA\Schema(type: 'string', format: 'uuid')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Nộp bài và chấm điểm thành công',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Chúc mừng! Bạn đã hoàn thành bài quiz đạt yêu cầu.'),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'score', type: 'number', format: 'float', example: 100.00),
                                new OA\Property(property: 'correct_count', type: 'integer', example: 1),
                                new OA\Property(property: 'total_questions', type: 'integer', example: 1),
                                new OA\Property(property: 'is_passed', type: 'boolean', example: true),
                                new OA\Property(property: 'passing_score', type: 'number', format: 'float', example: 80.00),
                                new OA\Property(
                                    property: 'details',
                                    type: 'array',
                                    items: new OA\Items(
                                        properties: [
                                            new OA\Property(property: 'quiz_id', type: 'string', format: 'uuid', example: 'e5f6g7h8-i9j0-k1l2-m3n4-o5p6q7r8s9t0'),
                                            new OA\Property(property: 'type', type: 'string', example: 'multiple_choice'),
                                            new OA\Property(property: 'order', type: 'integer', example: 1),
                                            new OA\Property(property: 'title', type: 'string', nullable: true, example: 'Câu 1: Quy hoạch phân khu'),
                                            new OA\Property(property: 'question', type: 'string', example: 'Giá trị cốt lõi đầu tiên của công ty là gì?'),
                                            new OA\Property(
                                                property: 'options',
                                                type: 'array',
                                                items: new OA\Items(
                                                    properties: [
                                                        new OA\Property(property: 'value', type: 'integer', example: 0),
                                                        new OA\Property(property: 'label', type: 'string', example: 'Hà Nội và TP.HCM')
                                                    ],
                                                    type: 'object'
                                                ),
                                                example: [
                                                    ['value' => 0, 'label' => 'Hà Nội và TP.HCM'],
                                                    ['value' => 1, 'label' => 'Đà Nẵng']
                                                ]
                                            ),
                                            new OA\Property(property: 'selected_option', type: 'integer', nullable: true, example: 0),
                                            new OA\Property(property: 'essay_answer', type: 'string', nullable: true, example: 'Nội dung tự luận'),
                                            new OA\Property(property: 'correct_option', type: 'integer', nullable: true, example: 0),
                                            new OA\Property(property: 'is_correct', type: 'boolean', nullable: true, example: true)
                                        ],
                                        type: 'object'
                                    )
                                )
                            ],
                            type: 'object'
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Chưa đăng nhập (Unauthenticated)'
            ),
            new OA\Response(
                response: 403,
                description: 'Tài khoản nhân viên bị khóa, chưa hoàn thành bài học, hoặc chưa tham gia khóa học',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Bạn cần hoàn thành bài học trước khi làm quiz.')
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Dữ liệu đầu vào không hợp lệ hoặc chưa hoàn thành tất cả câu hỏi',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Vui lòng hoàn thành tất cả câu hỏi.')
                    ]
                )
            ),
            new OA\Response(
                response: 500,
                description: 'Không thể nộp bài kiểm tra'
            )
        ]
    )]
    public function submitQuiz(SubmitQuizRequest $request, string $id): JsonResponse
    {
        $result = $this->learningService->submitCourseQuiz(
            $id,
            (array) $request->input('answers'),
            (bool) $request->input('is_timeout', false),
            $request->user()->id
        );

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Post(
        path: '/api/v1/learning/courses/{id}/complete',
        description: 'Kiểm tra tiến độ học tập và điểm số bài quiz cuối khóa để ghi nhận nhân viên đã hoàn thành khóa học.',
        summary: 'Ghi nhận hoàn thành khóa học (UC-057)',
        security: [['sanctum' => []]],
        tags: ['Learning'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'ID của khóa học (UUID)',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Khóa học được ghi nhận hoàn thành thành công',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Bạn đã hoàn thành khóa học.'),
                        new OA\Property(
                            property: 'data',
                            ref: '#/components/schemas/CourseEnrollment'
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Chưa đăng nhập (Unauthenticated)'
            ),
            new OA\Response(
                response: 403,
                description: 'Không đủ điều kiện (chưa hoàn thành tất cả bài học hoặc điểm quiz cuối khóa < 8) hoặc chưa đăng ký khóa học',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Bạn chưa hoàn thành tất cả bài học.')
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Khóa học không tồn tại',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Không tìm thấy khóa học bắt buộc.')
                    ]
                )
            ),
            new OA\Response(
                response: 500,
                description: 'Không thể cập nhật trạng thái khóa học'
            )
        ]
    )]
    public function complete(ViewCourseDetailsRequest $request, string $id): JsonResponse
    {
        $result = $this->learningService->completeCourse($id, $request->user()->id);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Get(
        path: '/api/v1/learning/courses/{id}/certificate',
        description: 'Tải thông tin chứng nhận hoàn thành khóa học của nhân viên bao gồm: Tên khóa học, Họ tên nhân viên, Ngày hoàn thành, Điểm số, Mã chứng nhận.',
        summary: 'Xem dữ liệu chứng nhận hoàn thành khóa học (UC-058)',
        security: [['sanctum' => []]],
        tags: ['Learning'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'ID của khóa học (UUID)',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Tải dữ liệu chứng nhận thành công',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Tải dữ liệu chứng nhận thành công.'),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'course_title', type: 'string', example: 'Khóa đào tạo văn hóa doanh nghiệp'),
                                new OA\Property(property: 'employee_name', type: 'string', example: 'Nguyễn Văn Nhân Viên'),
                                new OA\Property(property: 'completed_at', type: 'string', format: 'date-time', example: '2026-05-19T08:15:23Z'),
                                new OA\Property(property: 'score', type: 'number', format: 'float', example: 10.00),
                                new OA\Property(property: 'certificate_code', type: 'string', format: 'uuid', example: 'a1b2c3d4-e5f6-7a8b-9c0d-1e2f3a4b5c6d')
                            ],
                            type: 'object'
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Chưa đăng nhập (Unauthenticated)'
            ),
            new OA\Response(
                response: 403,
                description: 'Nhân viên chưa hoàn thành khóa học hoặc khóa học không hỗ trợ chứng nhận',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Bạn chưa hoàn thành khóa học.')
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Khóa học không tồn tại',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Không tìm thấy khóa học bắt buộc.')
                    ]
                )
            )
        ]
    )]
    public function getCertificate(ViewCourseDetailsRequest $request, string $id): JsonResponse
    {
        $result = $this->learningService->getCertificate($id, $request->user()->id);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess($result->getData(), $result->getMessage());
    }

    #[OA\Get(
        path: '/api/v1/learning/courses/{id}/certificate/download',
        description: 'Tải file chứng nhận định dạng văn bản (.txt) về thiết bị.',
        summary: 'Tải file chứng nhận hoàn thành khóa học (UC-058)',
        security: [['sanctum' => []]],
        tags: ['Learning'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'ID của khóa học (UUID)',
                schema: new OA\Schema(type: 'string', format: 'uuid')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'File chứng nhận được tải xuống thành công',
                content: new OA\MediaType(
                    mediaType: 'text/plain',
                    schema: new OA\Schema(type: 'string')
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Chưa đăng nhập (Unauthenticated)'
            ),
            new OA\Response(
                response: 403,
                description: 'Nhân viên chưa hoàn thành khóa học hoặc khóa học không hỗ trợ chứng nhận',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Bạn chưa hoàn thành khóa học.')
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Khóa học không tồn tại'
            ),
            new OA\Response(
                response: 500,
                description: 'Không thể tải chứng nhận',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Không thể tải chứng nhận.')
                    ]
                )
            )
        ]
    )]
    public function downloadCertificate(ViewCourseDetailsRequest $request, string $id)
    {
        $result = $this->learningService->downloadCertificate($id, $request->user()->id);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        $data = $result->getData();

        return response()->streamDownload(function () use ($data) {
            echo $data['content'];
        }, $data['filename'], [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }

    #[OA\Post(
        path: '/api/v1/learning/courses/{id}/quiz/draft',
        description: 'Cho phép nhân viên lưu nháp tiến trình làm bài kiểm tra để tiếp tục hoàn thiện sau.',
        summary: 'Lưu tạm bài làm quiz (lưu bản nháp) (UC-059)',
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(
                        property: 'answers',
                        type: 'array',
                        items: new OA\Items(
                            properties: [
                                new OA\Property(property: 'quiz_id', type: 'string', format: 'uuid', example: 'd3b07384-d113-4ec2-a5d6-c734b1234567'),
                                new OA\Property(property: 'selected_option', type: 'integer', example: 1, nullable: true)
                            ],
                            type: 'object'
                        )
                    )
                ]
            )
        ),
        tags: ['Learning'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'ID của khóa học (UUID) có bài quiz cần lưu nháp',
                schema: new OA\Schema(type: 'string', format: 'uuid')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Lưu bản nháp thành công',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Lưu bản nháp thành công.')
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Chưa đăng nhập (Unauthenticated)'
            ),
            new OA\Response(
                response: 403,
                description: 'Nhân viên chưa hoàn thành xem video bài học',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Bạn cần hoàn thành bài học trước khi làm quiz.')
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Không có dữ liệu câu trả lời nháp để lưu',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Không có dữ liệu để lưu.')
                    ]
                )
            ),
            new OA\Response(
                response: 500,
                description: 'Lỗi lưu bản nháp',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Không thể lưu bản nháp.')
                    ]
                )
            )
        ]
    )]
    public function saveDraft(SaveQuizDraftRequest $request, string $id): JsonResponse
    {
        $result = $this->learningService->saveCourseQuizDraft($id, $request->input('answers'), $request->user()->id);

        if ($result->isError()) {
            return $this->sendError($result->getMessage(), $result->getCode());
        }

        return $this->sendSuccess(null, $result->getMessage());
    }
}
