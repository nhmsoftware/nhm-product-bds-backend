<?php

declare(strict_types=1);

namespace App\Modules\Learning\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use OpenApi\Attributes as OA;

/**
 * Class CourseQuiz
 *
 * @property string $id
 * @property string $lesson_id
 * @property string $question
 * @property array|null $options
 * @property int $correct_option
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read Lesson|null $lesson
 * @mixin \Eloquent
 */
#[OA\Schema(
    schema: 'CourseQuiz',
    title: 'CourseQuiz Model',
    description: 'Câu hỏi trắc nghiệm kiểm tra bài học',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid', example: 'e5f6g7h8-i9j0-k1l2-m3n4-o5p6q7r8s9t0', description: 'ID duy nhất của câu hỏi'),
        new OA\Property(property: 'lesson_id', type: 'string', format: 'uuid', example: 'f87a8b9c-d0e1-4f2a-b3c4-d5e6f7a8b9c0', description: 'ID bài học sở hữu'),
        new OA\Property(property: 'question', type: 'string', example: 'Giá trị cốt lõi đầu tiên của công ty là gì?', description: 'Nội dung câu hỏi'),
        new OA\Property(
            property: 'options',
            type: 'array',
            items: new OA\Items(type: 'string'),
            example: ["Trung thực", "Tận tâm", "Tốc độ", "Đột phá"],
            description: 'Các phương án lựa chọn'
        ),
        new OA\Property(property: 'correct_option', type: 'integer', example: 0, description: 'Chỉ mục phương án đúng (0-based)'),
    ]
)]
class CourseQuiz extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $table = 'course_quizzes';

    protected $fillable = [
        'lesson_id',
        'question',
        'options',
        'correct_option',
    ];

    protected $casts = [
        'id' => 'string',
        'lesson_id' => 'string',
        'options' => 'array',
        'correct_option' => 'integer',
    ];

    // ─── Relationships ───────────────────────────────────────────

    /**
     * Bài học chứa câu hỏi quiz này.
     *
     * @return BelongsTo
     */
    public function lesson(): BelongsTo
    {
        return $this->belongsTo(CourseLesson::class, 'lesson_id');
    }
}
