<?php

declare(strict_types=1);

namespace App\Modules\Project\DTO;

use Illuminate\Http\Request;

final class AssignPermissionDTO
{
    public function __construct(
        public readonly string $assignableId,
        public readonly string $assignableType,
        public readonly array $permissions,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            assignableId: $request->input('assignable_id'),
            assignableType: $request->input('assignable_type'), // 'user' or 'department'
            permissions: $request->input('permissions', []),
        );
    }
}
