<?php

namespace App\Modules\Auth\DTO;

use Illuminate\Http\Request;

final class ForgotPasswordDTO
{
    public function __construct(
        public readonly string $username, // email hoặc phone
    ) {
    }

    public static function fromRequest(Request $request): self
    {
        return new self(
            username: $request->validated('username'),
        );
    }
}
