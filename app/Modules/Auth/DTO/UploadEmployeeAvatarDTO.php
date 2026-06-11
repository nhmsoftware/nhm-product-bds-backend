<?php

namespace App\Modules\Auth\DTO;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

final class UploadEmployeeAvatarDTO
{
    public function __construct(
        public readonly string $userId,
        public readonly UploadedFile $avatar,
    ) {
    }

    public static function fromRequest(Request $request): self
    {
        return new self(
            userId: (string) auth('api')->id(),
            avatar: $request->file('avatar')
        );
    }
}
