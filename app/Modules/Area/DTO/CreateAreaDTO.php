<?php

declare(strict_types=1);

namespace App\Modules\Area\DTO;

final class CreateAreaDTO
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $sales_board_image = null,
        public readonly ?string $sales_board_iframe = null,
        public readonly ?string $planning_check_url = null,
        public readonly ?array $sales_board_images = null,
        public readonly ?float $area_size = null,
        public readonly ?string $direction = null,
        public readonly ?int $status = null,
        public readonly int $total_lots = 0,
        public readonly ?string $project_id = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            sales_board_image: $data['sales_board_image'] ?? null,
            sales_board_iframe: $data['sales_board_iframe'] ?? null,
            planning_check_url: $data['planning_check_url'] ?? null,
            sales_board_images: $data['sales_board_images'] ?? null,
            area_size: isset($data['area_size']) ? (float) $data['area_size'] : null,
            direction: $data['direction'] ?? null,
            status: isset($data['status']) ? (int) $data['status'] : null,
            total_lots: isset($data['total_lots']) ? (int) $data['total_lots'] : 0,
            project_id: $data['project_id'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'sales_board_image' => $this->sales_board_image,
            'sales_board_iframe' => $this->sales_board_iframe,
            'planning_check_url' => $this->planning_check_url,
            'sales_board_images' => $this->sales_board_images,
            'area_size' => $this->area_size,
            'direction' => $this->direction,
            'status' => $this->status,
            'total_lots' => $this->total_lots,
            'project_id' => $this->project_id,
        ];
    }
}
