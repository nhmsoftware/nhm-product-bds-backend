<?php

declare(strict_types=1);

namespace App\Modules\Learning\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OpenApi\Attributes as OA;

/**
 * Class Course
 *
 * @property string $id
 * @property string $title
 * @property string $description
 * @property string $thumbnail
 * @property bool $is_required
 * @property string $department
 * @property string $job_position
 * @property int $order
 * @property bool $is_active
 * @property bool $has_certificate
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection|Lessons[] $lessons
 * @property-read \Illuminate\Database\Eloquent\Collection|Enrollments[] $enrollments
 * @mixin \Eloquent
 */
#[OA\Schema(
    schema: 'Course',
    title: 'Course Model',
    description: 'Thông tin khóa học',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid', example: 'd3b07384-d113-4ec2-a5d6-c734b1234567', description: 'ID duy nhất của khóa học'),
        new OA\Property(property: 'title', type: 'string', example: 'Khóa đào tạo văn hóa doanh nghiệp', description: 'Tên khóa học'),
        new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Giới thiệu về giá trị cốt lõi và sứ mệnh của công ty.', description: 'Mô tả khóa học'),
        new OA\Property(property: 'thumbnail', type: 'string', nullable: true, example: 'https://bds-app.s3.amazonaws.com/thumbnails/culture.jpg', description: 'URL ảnh cover'),
        new OA\Property(property: 'is_required', type: 'boolean', example: true, description: 'Có bắt buộc hay không'),
        new OA\Property(property: 'department', type: 'string', nullable: true, example: 'Kinh doanh', description: 'Phòng ban được phân bổ'),
        new OA\Property(property: 'job_position', type: 'string', nullable: true, example: 'Nhân viên', description: 'Vị trí công việc được phân bổ'),
        new OA\Property(property: 'order', type: 'integer', example: 1, description: 'Thứ tự hiển thị'),
        new OA\Property(property: 'is_active', type: 'boolean', example: true, description: 'Trạng thái hoạt động'),
        new OA\Property(property: 'has_certificate', type: 'boolean', example: true, description: 'Khóa học có cấp chứng nhận hay không'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2026-05-19T08:15:23Z'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', example: '2026-05-19T08:15:23Z'),
    ]
)]
class Course extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $table = 'courses';

    protected $fillable = [
        'title',
        'description',
        'thumbnail',
        'is_required',
        'department',
        'job_position',
        'order',
        'is_active',
        'has_certificate',
    ];

    protected $casts = [
        'id' => 'string',
        'is_required' => 'boolean',
        'is_active' => 'boolean',
        'has_certificate' => 'boolean',
        'order' => 'integer',
    ];

    // ─── Relationships ───────────────────────────────────────────

    /**
     * Danh sách bài học thuộc khóa học này.
     *
     * @return HasMany
     */
    public function lessons(): HasMany
    {
        return $this->hasMany(CourseLesson::class, 'course_id')->orderBy('order', 'asc');
    }

    /**
     * Danh sách ghi nhận tham gia khóa học.
     *
     * @return HasMany
     */
    public function enrollments(): HasMany
    {
        return $this->hasMany(CourseEnrollment::class, 'course_id');
    }

    /**
     * Danh sách toàn bộ câu hỏi quiz thuộc các bài học của khóa học này.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function quizzes(): \Illuminate\Database\Eloquent\Relations\HasManyThrough
    {
        return $this->hasManyThrough(
            CourseQuiz::class,
            CourseLesson::class,
            'course_id',
            'lesson_id',
            'id',
            'id'
        );
    }
}
