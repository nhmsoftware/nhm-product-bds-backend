<?php

namespace App\Modules\News\DTO;

final class GetNewsListDTO
{
    public function __construct(
        public readonly ?string $category = null,
        public readonly ?string $search = null,
        public readonly int $perPage = 10,
        public readonly int $page = 1,
    ) {
    }

    public static function fromRequest($request): self
    {
        $category = $request->query('category');
        if ($category === 'all' || $category === 'Tất cả') {
            $category = null;
        }

        return new self(
            category: $category,
            search: $request->query('search'),
            perPage: (int) $request->query('per_page', 10),
            page: (int) $request->query('page', 1),
        );
    }

    public function toArray(): array
    {
        return [
            'category' => $this->category,
            'search' => $this->search,
            'per_page' => $this->perPage,
            'page' => $this->page,
        ];
    }
}
