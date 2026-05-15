<?php

namespace App\Modules\Project\DTO;

use Illuminate\Http\Request;

final class ProjectListDTO
{
    public function __construct(
        public readonly ?string $search = null,
        public readonly ?string $status = null,
        public readonly ?string $type = null,
        public readonly ?string $location = null,
        public readonly ?string $minPrice = null,
        public readonly ?string $maxPrice = null,
        public readonly int $perPage = 10,
        public readonly int $page = 1
    ) {
    }

    public static function fromRequest(Request $request): self
    {
        return new self(
            search: $request->query('search'),
            status: $request->query('status'),
            type: $request->query('type'),
            location: $request->query('location'),
            minPrice: $request->query('min_price'),
            maxPrice: $request->query('max_price'),
            perPage: (int) $request->query('per_page', 10),
            page: (int) $request->query('page', 1)
        );
    }

    public function toArray(): array
    {
        return [
            'search' => $this->search,
            'status' => $this->status,
            'type' => $this->type,
            'location' => $this->location,
            'min_price' => $this->minPrice,
            'max_price' => $this->maxPrice,
            'per_page' => $this->perPage,
            'page' => $this->page,
        ];
    }
}
