<?php

declare(strict_types=1);

namespace App\Modules\Project\DTO;

use Illuminate\Http\Request;
use App\Modules\Project\Models\Enums\ProjectStatus;

final class UpdateProjectDTO
{
    public function __construct(
        public readonly ?string $name = null,
        public readonly ?string $code = null,
        public readonly ?string $location = null,
        public readonly ?string $price = null,
        public readonly ?ProjectStatus $status = null,
        public readonly ?string $type = null,
        public readonly ?string $image = null,
        public readonly ?string $banner = null,
        public readonly ?bool $isPublic = null,
        public readonly ?string $description = null,
        public readonly ?array $keywords = null,
        public readonly ?array $amenities = null,
        public readonly ?array $floorPlans = null,
        public readonly ?array $legalInfo = null,
        public readonly ?string $brochure = null,
        public readonly ?array $contactInfo = null,
        public readonly ?string $googleMapsUrl = null,
        public readonly ?array $planningInfo = null,
        public readonly ?string $branch = null,
        public readonly ?int $totalLots = null,
        public readonly ?int $remainingLots = null,
        public readonly ?bool $isFeatured = null,
        public readonly ?bool $isLocked = null,
        public readonly ?array $areas = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            name: $request->input('name'),
            code: $request->input('code'),
            location: $request->input('location'),
            price: $request->has('price') ? (string) $request->input('price') : null,
            status: $request->has('status') ? ProjectStatus::from((int) $request->input('status')) : null,
            type: $request->input('type'),
            image: $request->input('image'),
            banner: $request->input('banner'),
            isPublic: $request->has('is_public') ? $request->boolean('is_public') : null,
            description: $request->input('description'),
            keywords: $request->input('keywords'),
            amenities: $request->input('amenities'),
            floorPlans: $request->input('floor_plans'),
            legalInfo: $request->input('legal_info'),
            brochure: $request->input('brochure'),
            contactInfo: $request->input('contact_info'),
            googleMapsUrl: $request->input('google_maps_url'),
            planningInfo: $request->input('planning_info'),
            branch: $request->input('branch'),
            totalLots: $request->has('total_lots') ? (int) $request->input('total_lots') : null,
            remainingLots: $request->has('remaining_lots') ? (int) $request->input('remaining_lots') : null,
            isFeatured: $request->has('is_featured') ? $request->boolean('is_featured') : null,
            isLocked: $request->has('is_locked') ? $request->boolean('is_locked') : null,
            areas: $request->input('areas'),
        );
    }

    public function toArray(): array
    {
        $data = [
            'name' => $this->name,
            'code' => $this->code,
            'location' => $this->location,
            'price' => $this->price,
            'status' => $this->status,
            'type' => $this->type,
            'image' => $this->image,
            'banner' => $this->banner,
            'is_public' => $this->isPublic,
            'description' => $this->description,
            'keywords' => $this->keywords,
            'amenities' => $this->amenities,
            'floor_plans' => $this->floorPlans,
            'legal_info' => $this->legalInfo,
            'brochure' => $this->brochure,
            'contact_info' => $this->contactInfo,
            'google_maps_url' => $this->googleMapsUrl,
            'planning_info' => $this->planningInfo,
            'branch' => $this->branch,
            'total_lots' => $this->totalLots,
            'remaining_lots' => $this->remainingLots,
            'is_featured' => $this->isFeatured,
            'is_locked' => $this->isLocked,
        ];

        return array_filter($data, fn($value) => $value !== null);
    }
}
