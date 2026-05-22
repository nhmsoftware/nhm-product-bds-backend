<?php

namespace App\Modules\News\DTO;

use Illuminate\Http\Request;

final class DeleteInternalPostDTO
{
    public function __construct(
        public readonly string $id,
        public readonly string $userId,
    ) {
    }

    public static function fromRequest(Request $request, string $id, string $userId): self
    {
        return new self(
            id: $id,
            userId: $userId,
        );
    }
}
