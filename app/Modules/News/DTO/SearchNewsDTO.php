<?php

namespace App\Modules\News\DTO;

final class SearchNewsDTO
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
            keyword: $request->input('keyword', $request->input('search', '')),
            perPage: (int) $request->input('per_page', 10),
            page: (int) $request->input('page', 1),
        );
    }
}
