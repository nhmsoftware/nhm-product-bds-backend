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
 * @property string $type
 * @property int $order
 * @property string|null $title
 * @property string|null $image_url
 * @property string|null $placeholder
 * @property array|null $options
 * @property int|null $correct_option
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
        new OA\Property(property: 'type', type: 'string', example: 'multiple_choice', description: 'Loại câu hỏi (multiple_choice, essay)'),
        new OA\Property(property: 'order', type: 'integer', example: 1, description: 'Thứ tự câu hỏi'),
        new OA\Property(property: 'title', type: 'string', nullable: true, example: 'Câu 1: Quy hoạch phân khu', description: 'Tiêu đề câu hỏi'),
        new OA\Property(property: 'question', type: 'string', example: 'Giá trị cốt lõi đầu tiên của công ty là gì?', description: 'Nội dung câu hỏi'),
        new OA\Property(property: 'image_url', type: 'string', nullable: true, example: 'https://example.com/image.jpg', description: 'Ảnh minh họa câu hỏi'),
        new OA\Property(property: 'placeholder', type: 'string', nullable: true, example: 'Nhập câu trả lời của bạn...', description: 'Gợi ý nhập cho câu tự luận'),
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
                ['value' => 1, 'label' => 'Đà Nẵng'],
                ['value' => 2, 'label' => 'Cần Thơ'],
                ['value' => 3, 'label' => 'Hải Phòng']
            ],
            description: 'Các phương án lựa chọn'
        ),
        new OA\Property(property: 'correct_option', type: 'integer', nullable: true, example: 0, description: 'Chỉ mục phương án đúng (0-based)'),
    ]
)]
class CourseQuiz extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $table = 'course_quizzes';

    protected $fillable = [
        'lesson_id',
        'type',
        'order',
        'title',
        'question',
        'image_url',
        'options',
        'placeholder',
        'correct_option',
    ];

    protected $casts = [
        'id' => 'string',
        'lesson_id' => 'string',
        'type' => 'string',
        'order' => 'integer',
        'correct_option' => 'integer',
    ];

    public function getOptionsAttribute($value)
    {
        return json_decode($value ?? '[]', true);
    }

    public function setOptionsAttribute($value): void
    {
        if (is_array($value)) {
            $normalized = [];
            foreach ($value as $idx => $item) {
                if (is_array($item)) {
                    $normalized[] = [
                        'value' => isset($item['value']) ? (int) $item['value'] : $idx + 1,
                        'label' => $item['label'] ?? ''
                    ];
                } else {
                    $normalized[] = [
                        'value' => $idx + 1,
                        'label' => (string) $item
                    ];
                }
            }
            $this->attributes['options'] = json_encode($normalized);
        } else {
            $this->attributes['options'] = null;
        }
    }

    protected static function booted(): void
    {
        static::deleting(function (CourseQuiz $quiz) {
            $hasAttempts = \App\Modules\Learning\Models\QuizAttempt::where('quiz_id', $quiz->id)->exists();
            if ($hasAttempts) {
                \Filament\Notifications\Notification::make()
                    ->title('Không thể xóa câu hỏi')
                    ->body('Câu hỏi này đã có nhân viên làm bài. Vui lòng xóa bài làm của nhân viên trước khi xóa câu hỏi.')
                    ->danger()
                    ->send();
                return false;
            }
        });
    }

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
