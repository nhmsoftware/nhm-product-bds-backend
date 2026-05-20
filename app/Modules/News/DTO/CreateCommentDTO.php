<?php

namespace App\Modules\News\DTO;

use Illuminate\Http\Request;

final class CreateCommentDTO
{
    public function __construct(
        public readonly string $newsId,
        public readonly string $userId,
        public readonly string $content,
    ) {
    }

    public static function fromRequest(Request $request, string $newsId, string $userId): self
    {
        return new self(
            newsId: $newsId,
            userId: $userId,
            content: $request->input('content', ''),
        );
    }
}
