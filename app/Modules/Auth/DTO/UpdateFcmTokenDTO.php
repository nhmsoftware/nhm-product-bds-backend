<?php

declare(strict_types=1);

namespace App\Modules\Auth\DTO;

use Illuminate\Http\Request;

readonly class UpdateFcmTokenDTO
{
    public function __construct(
        public string $userId,
        public string $fcmToken,
    ) {
    }

    public static function fromRequest(Request $request): self
    {
        return new self(
            userId: (string) auth('api')->id(),
            fcmToken: (string) $request->input('fcm_token')
        );
    }
}
