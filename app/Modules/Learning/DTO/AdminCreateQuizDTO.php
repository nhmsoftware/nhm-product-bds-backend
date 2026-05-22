<?php

namespace App\Modules\Learning\DTO;

use Illuminate\Http\Request;

final class AdminCreateQuizDTO
{
    public function __construct(
        public readonly string $lessonId,
        public readonly string $question,
        public readonly array $options,
        public readonly int $correctOption,
    ) {
    }

    public static function fromRequest(Request $request): self
    {
        return new self(
            lessonId: $request->input('lesson_id'),
            question: $request->input('question'),
            options: $request->input('options'),
            correctOption: (int) $request->input('correct_option'),
        );
    }

    public function toArray(): array
    {
        return [
            'lesson_id' => $this->lessonId,
            'question' => $this->question,
            'options' => $this->options,
            'correct_option' => $this->correctOption,
        ];
    }
}
