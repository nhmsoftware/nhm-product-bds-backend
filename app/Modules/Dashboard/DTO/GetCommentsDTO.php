<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\DTO;

use Illuminate\Http\Request;

final class GetCommentsDTO
{
    public function __construct(
        public readonly ?string $keyword = null,
        public readonly ?string $type = null,
        public readonly ?string $project_id = null,
        public readonly ?string $area_id = null,
        public readonly int $page = 1,
        public readonly int $per_page = 15
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            keyword: $request->query('keyword'),
            type: $request->query('type'),
            project_id: $request->query('project_id'),
            area_id: $request->query('area_id'),
            page: (int) $request->query('page', 1),
            per_page: (int) $request->query('per_page', 15)
        );
    }

    public function toArray(): array
    {
        return [
            'keyword' => $this->keyword,
            'type' => $this->type,
            'project_id' => $this->project_id,
            'area_id' => $this->area_id,
        ];
    }
}
