<?php

namespace App\Modules\Auth\DTO;

use Illuminate\Http\Request;

final class LoginDTO
{
    public function __construct(
        public readonly string $username, // Có thể là email hoặc phone
        public readonly string $password,
        public readonly bool $remember = false,
    ) {
    }

    public static function fromRequest(Request $request): self
    {
        return new self(
            username: $request->validated('username'),
            password: $request->validated('password'),
            remember: (bool) $request->validated('remember', false),
        );
    }
}
