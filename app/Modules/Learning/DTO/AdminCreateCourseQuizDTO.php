<?php

namespace App\Modules\Learning\DTO;

use Illuminate\Http\Request;

final class AdminCreateCourseQuizDTO
{
    public function __construct(
        public readonly string $title,
        public readonly ?string $description,
        public readonly float $passingScore,
        public readonly array $questions
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            title: $request->input('title') ?? '',
            description: $request->input('description'),
            passingScore: (float) ($request->input('passing_score') ?? 0.0),
            questions: $request->input('questions') ?? []
        );
    }
}
