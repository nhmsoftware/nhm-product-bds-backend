<?php

declare(strict_types=1);

namespace App\Modules\Learning\Models;

use App\Modules\Auth\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OpenApi\Attributes as OA;
use App\Modules\Learning\Models\Enums\CourseEnrollmentStatus;/**
 * Class CourseEnrollment
 *
 * @property string $id
 * @property string $user_id
 * @property string $course_id
 * @property CourseEnrollmentStatus $status
 * @property string $progress_percent
 * @property \Illuminate\Support\Carbon|null $completed_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read User|null $user
 * @property-read Course|null $course
 * @property-read \Illuminate\Database\Eloquent\Collection|LessonProgress[] $lessonProgress
 * @mixin \Eloquent
 */
#[OA\Schema(
    schema: 'CourseEnrollment',
    title: 'CourseEnrollment Model',
    description: 'Thông tin đăng ký và tiến độ khóa học của nhân viên',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid', example: 'a1b2c3d4-e5f6-7a8b-9c0d-1e2f3a4b5c6d', description: 'ID duy nhất của lượt đăng ký'),
        new OA\Property(property: 'user_id', type: 'string', format: 'uuid', example: 'uuid-user-string', description: 'ID của nhân viên'),
        new OA\Property(property: 'course_id', type: 'string', format: 'uuid', example: 'd3b07384-d113-4ec2-a5d6-c734b1234567', description: 'ID của khóa học'),
        new OA\Property(property: 'status', type: 'integer', example: \App\Modules\Learning\Models\Enums\CourseEnrollmentStatus::IN_PROGRESS->value, description: 'Trạng thái học tập: 1 (not_started), 2 (in_progress), 3 (completed)'),
        new OA\Property(property: 'progress_percent', type: 'number', format: 'float', example: 50.00, description: 'Tiến độ hoàn thành (%)'),
        new OA\Property(property: 'completed_at', type: 'string', format: 'date-time', nullable: true, example: '2026-05-19T08:15:23Z', description: 'Thời điểm hoàn thành khóa học'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
class CourseEnrollment extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'course_enrollments';

    protected $fillable = [
        'user_id',
        'course_id',
        'status',
        'progress_percent',
        'completed_at',
        'quiz_attempt_id',
        'quiz_status',
        'quiz_started_at',
        'quiz_expires_at',
        'quiz_remaining_seconds',
        'quiz_last_saved_at',
    ];

    protected $casts = [
        'id' => 'string',
        'user_id' => 'string',
        'course_id' => 'string',
        'progress_percent' => 'decimal:2',
        'completed_at' => 'datetime',
        'quiz_started_at' => 'datetime',
        'quiz_expires_at' => 'datetime',
        'quiz_last_saved_at' => 'datetime',
        'quiz_remaining_seconds' => 'integer',
        'status' => CourseEnrollmentStatus::class,
    ];

    // ─── Relationships ───────────────────────────────────────────

    /**
     * Nhân viên đăng ký học.
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Khóa học được đăng ký.
     *
     * @return BelongsTo
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    /**
     * Danh sách tiến độ chi tiết từng bài học.
     *
     * @return HasMany
     */
    public function lessonProgress(): HasMany
    {
        return $this->hasMany(LessonProgress::class, 'enrollment_id');
    }

    public function setStatusAttribute($value)
    {
        if ($value === null) {
            $this->attributes['status'] = null;
            return;
        }
        $this->attributes['status'] = $value instanceof CourseEnrollmentStatus ? $value->value : CourseEnrollmentStatus::deserialize($value)->value;
    }

    public function toArray()
    {
        $array = parent::toArray();
        if (isset($array['status']) && $this->status instanceof CourseEnrollmentStatus) {
            $array['status'] = strtolower($this->status->name);
        }
        return $array;
    }
}
