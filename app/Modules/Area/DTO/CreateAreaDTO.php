<?php

declare(strict_types=1);

namespace App\Modules\Area\DTO;

final class CreateAreaDTO
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $sales_board_image = null,
        public readonly int $total_lots = 0,
        public readonly ?string $project_id = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            sales_board_image: $data['sales_board_image'] ?? null,
            total_lots: isset($data['total_lots']) ? (int) $data['total_lots'] : 0,
            project_id: $data['project_id'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'sales_board_image' => $this->sales_board_image,
            'total_lots' => $this->total_lots,
            'project_id' => $this->project_id,
        ];
    }
}
