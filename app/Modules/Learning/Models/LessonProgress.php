<?php

declare(strict_types=1);

namespace App\Modules\Learning\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OpenApi\Attributes as OA;

/**
 * Class LessonProgress
 *
 * @property string $id
 * @property string $enrollment_id
 * @property string $lesson_id
 * @property bool $is_completed
 * @property \Illuminate\Support\Carbon|null $completed_at
 * @property int $current_watch_seconds
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Enrollment|null $enrollment
 * @property-read Lesson|null $lesson
 * @mixin \Eloquent
 */
#[OA\Schema(
    schema: 'LessonProgress',
    title: 'LessonProgress Model',
    description: 'Tiến độ học bài học của nhân viên',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid', example: 'uuid-string'),
        new OA\Property(property: 'enrollment_id', type: 'string', format: 'uuid', example: 'uuid-enrollment-string'),
        new OA\Property(property: 'lesson_id', type: 'string', format: 'uuid', example: 'uuid-lesson-string'),
        new OA\Property(property: 'is_completed', type: 'boolean', example: false),
        new OA\Property(property: 'completed_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'current_watch_seconds', type: 'integer', example: 120, description: 'Thời lượng video đã xem hiện tại (giây)'),
    ]
)]
class LessonProgress extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'lesson_progress';

    protected $fillable = [
        'enrollment_id',
        'lesson_id',
        'is_completed',
        'completed_at',
        'current_watch_seconds',
    ];

    protected $casts = [
        'id' => 'string',
        'enrollment_id' => 'string',
        'lesson_id' => 'string',
        'is_completed' => 'boolean',
        'completed_at' => 'datetime',
        'current_watch_seconds' => 'integer',
    ];

    // ─── Relationships ───────────────────────────────────────────

    /**
     * Lượt đăng ký khóa học tương ứng.
     *
     * @return BelongsTo
     */
    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(CourseEnrollment::class, 'enrollment_id');
    }

    /**
     * Bài học tương ứng.
     *
     * @return BelongsTo
     */
    public function lesson(): BelongsTo
    {
        return $this->belongsTo(CourseLesson::class, 'lesson_id');
    }
}
