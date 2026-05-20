<?php

namespace App\Modules\Learning\Models;

use App\Modules\Auth\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'CourseEnrollment',
    title: 'CourseEnrollment Model',
    description: 'Thông tin đăng ký và tiến độ khóa học của nhân viên',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid', example: 'a1b2c3d4-e5f6-7a8b-9c0d-1e2f3a4b5c6d', description: 'ID duy nhất của lượt đăng ký'),
        new OA\Property(property: 'user_id', type: 'string', format: 'uuid', example: 'uuid-user-string', description: 'ID của nhân viên'),
        new OA\Property(property: 'course_id', type: 'string', format: 'uuid', example: 'd3b07384-d113-4ec2-a5d6-c734b1234567', description: 'ID của khóa học'),
        new OA\Property(property: 'status', type: 'string', enum: ['not_started', 'in_progress', 'completed'], example: 'in_progress', description: 'Trạng thái học tập'),
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
    ];

    protected $casts = [
        'id' => 'string',
        'user_id' => 'string',
        'course_id' => 'string',
        'progress_percent' => 'decimal:2',
        'completed_at' => 'datetime',
    ];

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
}
