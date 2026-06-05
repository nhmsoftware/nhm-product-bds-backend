<?php

declare(strict_types=1);

namespace App\Modules\Area\DTO;

use Illuminate\Http\Request;

final class CreateAreaCommentDTO
{
    public function __construct(
        public readonly string $areaId,
        public readonly string $userId,
        public readonly string $content,
    ) {
    }

    public static function fromRequest(Request $request, string $areaId, string $userId): self
    {
        return new self(
            areaId: $areaId,
            userId: $userId,
            content: $request->input('content', ''),
        );
    }
}
