<?php

namespace App\Modules\Learning\DTO;

use Illuminate\Http\Request;

final class AdminUpdateQuizDTO
{
    public function __construct(
        public readonly ?string $question,
        public readonly ?array $options,
        public readonly ?int $correctOption,
    ) {
    }

    public static function fromRequest(Request $request): self
    {
        return new self(
            question: $request->input('question'),
            options: $request->input('options'),
            correctOption: $request->has('correct_option') ? (int) $request->input('correct_option') : null,
        );
    }

    public function toArray(): array
    {
        $data = [];
        if ($this->question !== null) $data['question'] = $this->question;
        if ($this->options !== null) $data['options'] = $this->options;
        if ($this->correctOption !== null) $data['correct_option'] = $this->correctOption;
        return $data;
    }
}
