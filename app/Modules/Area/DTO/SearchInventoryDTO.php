<?php

declare(strict_types=1);

namespace App\Modules\Area\DTO;

final class SearchInventoryDTO
{
    public function __construct(
        public readonly string $keyword,
        public readonly int $perPage = 10,
        public readonly int $page = 1,
    ) {
    }

    public static function fromRequest($request): self
    {
        return new self(
            keyword: (string) $request->query('keyword', $request->query('search', $request->query('q', ''))),
            perPage: (int) $request->query('per_page', 10),
            page: (int) $request->query('page', 1),
        );
    }
}
