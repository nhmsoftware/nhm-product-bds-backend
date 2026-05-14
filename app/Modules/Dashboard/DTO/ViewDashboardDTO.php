<?php

namespace App\Modules\Dashboard\DTO;

final class ViewDashboardDTO
{
    public function __construct(
        public readonly string $userId,
        public readonly string $role,
    ) {
    }

    public static function fromRequest($request): self
    {
        return new self(
            userId: (string) $request->user()->id,
            role: (string) $request->user()->role,
        );
    }
}
