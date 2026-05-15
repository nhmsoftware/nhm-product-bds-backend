<?php

namespace App\Modules\Planning\DTO;

use Illuminate\Http\Request;

final class PlanningListDTO
{
    public function __construct(
        public readonly ?string $search = null,
        public readonly ?string $city = null,
        public readonly int $perPage = 10,
        public readonly int $page = 1,
    ) {
    }

    public static function fromRequest(Request $request): self
    {
        return new self(
            search: $request->query('search'),
            city: $request->query('city'),
            perPage: (int) $request->query('per_page', 10),
            page: (int) $request->query('page', 1),
        );
    }

    public function toArray(): array
    {
        return [
            'search' => $this->search,
            'city' => $this->city,
            'per_page' => $this->perPage,
            'page' => $this->page,
        ];
    }
}
