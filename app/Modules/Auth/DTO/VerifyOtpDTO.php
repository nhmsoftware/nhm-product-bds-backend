<?php

namespace App\Modules\Auth\DTO;

use Illuminate\Http\Request;

final class VerifyOtpDTO
{
    public function __construct(
        public readonly string $username,
        public readonly string $otp,
    ) {
    }

    public static function fromRequest(Request $request): self
    {
        return new self(
            username: $request->validated('username'),
            otp: $request->validated('otp'),
        );
    }
}
