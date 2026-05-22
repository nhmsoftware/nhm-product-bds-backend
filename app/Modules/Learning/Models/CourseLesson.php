<?php

declare(strict_types=1);

namespace App\Modules\Learning\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OpenApi\Attributes as OA;

/**
 * Class CourseLesson
 *
 * @property string $id
 * @property string $course_id
 * @property string $title
 * @property string $content
 * @property string $video_url
 * @property int $duration_minutes
 * @property int $order
 * @property bool $is_active
 * @property array|null $attachments
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read Course|null $course
 * @property-read \Illuminate\Database\Eloquent\Collection|Quizzes[] $quizzes
 * @mixin \Eloquent
 */
#[OA\Schema(
    schema: 'CourseLesson',
    title: 'CourseLesson Model',
    description: 'Thông tin bài học thuộc khóa học',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid', example: 'f87a8b9c-d0e1-4f2a-b3c4-d5e6f7a8b9c0', description: 'ID duy nhất của bài học'),
        new OA\Property(property: 'course_id', type: 'string', format: 'uuid', example: 'd3b07384-d113-4ec2-a5d6-c734b1234567', description: 'ID khóa học sở hữu'),
        new OA\Property(property: 'title', type: 'string', example: 'Bài 1: Tổng quan về doanh nghiệp', description: 'Tên bài học'),
        new OA\Property(property: 'content', type: 'string', nullable: true, example: 'Nội dung chi tiết bài học...', description: 'Nội dung text/HTML'),
        new OA\Property(property: 'video_url', type: 'string', nullable: true, example: 'https://bds-app.s3.amazonaws.com/videos/lesson1.mp4', description: 'URL video bài học'),
        new OA\Property(property: 'duration_minutes', type: 'integer', example: 15, description: 'Thời lượng video (phút)'),
        new OA\Property(property: 'order', type: 'integer', example: 1, description: 'Thứ tự trong khóa học'),
        new OA\Property(property: 'is_active', type: 'boolean', example: true, description: 'Trạng thái bài học (Mở khóa / Khóa)'),
        new OA\Property(
            property: 'attachments',
            type: 'array',
            items: new OA\Items(
                properties: [
                    new OA\Property(property: 'type', type: 'string', example: 'pdf'),
                    new OA\Property(property: 'url', type: 'string', example: 'https://example.com/file.pdf'),
                    new OA\Property(property: 'name', type: 'string', example: 'report.pdf')
                ],
                type: 'object'
            ),
            nullable: true,
            description: 'Danh sách tài liệu đính kèm'
        ),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
class CourseLesson extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $table = 'course_lessons';

    protected $fillable = [
        'course_id',
        'title',
        'content',
        'video_url',
        'duration_minutes',
        'order',
        'is_active',
        'attachments',
    ];

    protected $casts = [
        'id' => 'string',
        'course_id' => 'string',
        'duration_minutes' => 'integer',
        'order' => 'integer',
        'is_active' => 'boolean',
        'attachments' => 'array',
    ];

    // ─── Relationships ───────────────────────────────────────────

    /**
     * Khóa học sở hữu bài học này.
     *
     * @return BelongsTo
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    /**
     * Danh sách câu hỏi quiz thuộc bài học này.
     *
     * @return HasMany
     */
    public function quizzes(): HasMany
    {
        return $this->hasMany(CourseQuiz::class, 'lesson_id');
    }
}
