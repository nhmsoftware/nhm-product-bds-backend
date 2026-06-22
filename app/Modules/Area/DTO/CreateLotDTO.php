<?php

declare(strict_types=1);

namespace App\Modules\Area\DTO;

final class CreateLotDTO
{
    public function __construct(
        public readonly string $code,
        public readonly int $status,
        public readonly ?float $area_size = null,
        public readonly ?string $direction = null,
        public readonly ?int $price = null,
        public readonly ?int $unit_price = null,
        public readonly ?float $frontage = null,
        public readonly ?bool $is_corner = null,
        public readonly ?string $legal = null,
        public readonly ?string $description = null,
        public readonly ?int $coordinate_x = null,
        public readonly ?int $coordinate_y = null,
        public readonly ?int $width = null,
        public readonly ?int $height = null,
        public readonly ?array $images = null,
        public readonly ?string $planning_id = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            code: $data['code'],
            status: (int) $data['status'],
            area_size: isset($data['area_size']) ? (float) $data['area_size'] : null,
            direction: $data['direction'] ?? null,
            price: isset($data['price']) ? (int) $data['price'] : null,
            unit_price: isset($data['unit_price']) ? (int) $data['unit_price'] : null,
            frontage: isset($data['frontage']) ? (float) $data['frontage'] : null,
            is_corner: isset($data['is_corner']) ? (bool) $data['is_corner'] : null,
            legal: $data['legal'] ?? null,
            description: $data['description'] ?? null,
            coordinate_x: isset($data['coordinate_x']) ? (int) $data['coordinate_x'] : null,
            coordinate_y: isset($data['coordinate_y']) ? (int) $data['coordinate_y'] : null,
            width: isset($data['width']) ? (int) $data['width'] : null,
            height: isset($data['height']) ? (int) $data['height'] : null,
            images: $data['images'] ?? null,
            planning_id: $data['planning_id'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'status' => $this->status,
            'area_size' => $this->area_size,
            'direction' => $this->direction,
            'price' => $this->price,
            'unit_price' => $this->unit_price,
            'frontage' => $this->frontage,
            'is_corner' => $this->is_corner,
            'legal' => $this->legal,
            'description' => $this->description,
            'coordinate_x' => $this->coordinate_x,
            'coordinate_y' => $this->coordinate_y,
            'width' => $this->width,
            'height' => $this->height,
            'images' => $this->images,
            'planning_id' => $this->planning_id,
        ];
    }
}
