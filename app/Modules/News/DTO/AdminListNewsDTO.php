<?php

declare(strict_types=1);

namespace App\Modules\News\DTO;

use Illuminate\Http\Request;

final class AdminListNewsDTO
{
    public function __construct(
        public readonly ?string $search = null,
        public readonly ?bool $isPublished = null,
        public readonly ?string $type = null, // 'public' | 'internal'
        public readonly int $perPage = 15,
        public readonly int $page = 1
    ) {
    }

    public static function fromRequest(Request $request): self
    {
        $isPublished = null;
        if ($request->has('is_published') && $request->query('is_published') !== '') {
            $isPublished = filter_var($request->query('is_published'), FILTER_VALIDATE_BOOLEAN);
        }

        return new self(
            search: $request->query('search'),
            isPublished: $isPublished,
            type: $request->query('type'),
            perPage: (int) $request->query('per_page', 15),
            page: (int) $request->query('page', 1)
        );
    }
}
