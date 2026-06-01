<?php

declare(strict_types=1);

namespace App\Modules\Learning\Models;

use App\Modules\Auth\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OpenApi\Attributes as OA;

/**
 * Class QuizAttempt
 *
 * @property string $id
 * @property string $user_id
 * @property string $quiz_id
 * @property int|null $selected_option
 * @property string|null $essay_answer
 * @property bool|null $is_correct
 * @property bool $is_draft
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read User|null $user
 * @property-read Quiz|null $quiz
 * @mixin \Eloquent
 */
#[OA\Schema(
    schema: 'QuizAttempt',
    title: 'QuizAttempt Model',
    description: 'Lịch sử trả lời câu hỏi trắc nghiệm của nhân viên',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid', example: 'uuid-string'),
        new OA\Property(property: 'user_id', type: 'string', format: 'uuid', example: 'uuid-user-string'),
        new OA\Property(property: 'quiz_id', type: 'string', format: 'uuid', example: 'uuid-quiz-string'),
        new OA\Property(property: 'selected_option', type: 'integer', nullable: true, example: 1),
        new OA\Property(property: 'essay_answer', type: 'string', nullable: true, example: 'Câu trả lời tự luận của tôi'),
        new OA\Property(property: 'is_correct', type: 'boolean', nullable: true, example: true),
        new OA\Property(property: 'is_draft', type: 'boolean', example: false),
    ]
)]
class QuizAttempt extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'quiz_attempts';

    protected $fillable = [
        'user_id',
        'quiz_id',
        'selected_option',
        'essay_answer',
        'is_correct',
        'is_draft',
    ];

    protected $casts = [
        'id' => 'string',
        'user_id' => 'string',
        'quiz_id' => 'string',
        'selected_option' => 'integer',
        'is_correct' => 'boolean',
        'is_draft' => 'boolean',
    ];

    // ─── Relationships ───────────────────────────────────────────

    /**
     * Nhân viên thực hiện quiz.
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Câu hỏi quiz tương ứng.
     *
     * @return BelongsTo
     */
    public function quiz(): BelongsTo
    {
        return $this->belongsTo(CourseQuiz::class, 'quiz_id');
    }
}
