<?php

declare(strict_types=1);

namespace App\Modules\Project\DTO;

use Illuminate\Http\Request;

final class ListAdminProjectDTO
{
    public function __construct(
        public readonly int $page = 1,
        public readonly int $perPage = 10,
        public readonly ?string $keyword = null,
        public readonly ?array $filters = null,
        public readonly string $sortBy = 'created_at',
        public readonly string $direction = 'desc',
        public readonly ?string $userRole = null,
        public readonly ?string $userBranch = null
    ) {}

    public static function fromRequest(Request $request): self
    {
        $user = $request->user();
        return new self(
            page: (int) $request->input('page', 1),
            perPage: (int) $request->input('per_page', 10),
            keyword: $request->input('keyword'),
            filters: $request->input('filters'),
            sortBy: $request->input('sort_by', 'created_at'),
            direction: $request->input('direction', 'desc'),
            userRole: $user && $user->role ? $user->role->name : null,
            userBranch: $user ? $user->department : null
        );
    }
}
