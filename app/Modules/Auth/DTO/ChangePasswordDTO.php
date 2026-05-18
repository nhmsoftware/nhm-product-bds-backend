<?php

namespace App\Modules\Auth\DTO;

use Illuminate\Http\Request;

final class ChangePasswordDTO
{
    /**
     * ChangePasswordDTO constructor.
     */
    public function __construct(
        public readonly string $userId,
        public readonly string $currentPassword,
        public readonly string $newPassword,
    ) {
    }

    /**
     * Create a DTO from a Request.
     */
    public static function fromRequest(Request $request): self
    {
        return new self(
            userId: (string) auth('api')->id(),
            currentPassword: $request->validated('current_password'),
            newPassword: $request->validated('new_password'),
        );
    }
}
