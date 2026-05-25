<?php

namespace App\Modules\Learning\DTO;

use Illuminate\Http\Request;

final class AdminUpdateCourseStatusDTO
{
    public function __construct(
        public readonly bool $isActive
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            isActive: $request->boolean('is_active')
        );
    }
}
