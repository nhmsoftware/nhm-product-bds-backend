<?php

namespace App\Modules\Auth\DTO;

use Illuminate\Http\Request;

final class ResetPasswordDTO
{
    public function __construct(
        public readonly string $username,
        public readonly string $otp, // Dùng OTP làm token xác thực bước cuối
        public readonly string $password,
        public readonly string $password_confirmation,
    ) {
    }

    public static function fromRequest(Request $request): self
    {
        return new self(
            username: $request->validated('username'),
            otp: $request->validated('otp'),
            password: $request->validated('password'),
            password_confirmation: $request->validated('password_confirmation'),
        );
    }
}
