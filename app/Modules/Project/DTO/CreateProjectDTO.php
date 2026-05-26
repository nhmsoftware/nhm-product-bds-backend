<?php

declare(strict_types=1);

namespace App\Modules\Project\DTO;

use Illuminate\Http\Request;
use App\Modules\Project\Models\Enums\ProjectStatus;

final class CreateProjectDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $location,
        public readonly string $price,
        public readonly ProjectStatus $status,
        public readonly string $type,
        public readonly string $image,
        public readonly string $banner,
        public readonly bool $isPublic,
        public readonly string $description,
        public readonly ?array $keywords = null,
        public readonly ?array $amenities = null,
        public readonly ?array $floorPlans = null,
        public readonly ?array $legalInfo = null,
        public readonly string $brochure = '',
        public readonly ?array $contactInfo = null,
        public readonly string $googleMapsUrl = '',
        public readonly ?array $planningInfo = null,
        public readonly ?string $branch = null,
        public readonly int $totalLots = 0,
        public readonly int $remainingLots = 0,
        public readonly bool $isFeatured = false,
        public readonly bool $isLocked = false,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            name: $request->input('name'),
            location: $request->input('location'),
            price: (string) $request->input('price'),
            status: ProjectStatus::from((int) $request->input('status')),
            type: $request->input('type'),
            image: $request->input('image', ''),
            banner: $request->input('banner', ''),
            isPublic: $request->boolean('is_public', true),
            description: $request->input('description', ''),
            keywords: $request->input('keywords'),
            amenities: $request->input('amenities'),
            floorPlans: $request->input('floor_plans'),
            legalInfo: $request->input('legal_info'),
            brochure: $request->input('brochure', ''),
            contactInfo: $request->input('contact_info'),
            googleMapsUrl: $request->input('google_maps_url', ''),
            planningInfo: $request->input('planning_info'),
            branch: $request->input('branch'),
            totalLots: (int) $request->input('total_lots', 0),
            remainingLots: (int) $request->input('remaining_lots', 0),
            isFeatured: $request->boolean('is_featured', false),
            isLocked: $request->boolean('is_locked', false)
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
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
    }
}
