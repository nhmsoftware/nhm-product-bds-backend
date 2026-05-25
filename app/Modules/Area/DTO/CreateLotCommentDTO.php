<?php

declare(strict_types=1);

namespace App\Modules\Area\DTO;

use Illuminate\Http\Request;

final class CreateLotCommentDTO
{
    public function __construct(
        public readonly string $lotId,
        public readonly string $userId,
        public readonly string $content,
    ) {
    }

    public static function fromRequest(Request $request, string $lotId, string $userId): self
    {
        return new self(
            lotId: $lotId,
            userId: $userId,
            content: $request->input('content', ''),
        );
    }
}
