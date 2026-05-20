<?php

namespace App\Modules\SiteTour\DTO;

use App\Modules\SiteTour\Http\Requests\CheckInSiteTourRequest;
use Illuminate\Http\UploadedFile;

final class CheckInSiteTourDTO
{
    public function __construct(
        public readonly string $userId,
        public readonly string $projectId,
        public readonly string $unitCode,
        public readonly string $customerName,
        public readonly UploadedFile $image,
        public readonly float $latitude,
        public readonly float $longitude
    ) {
    }

    public static function fromRequest(CheckInSiteTourRequest $request, string $userId): self
    {
        return new self(
            userId: $userId,
            projectId: (string) $request->input('project_id'),
            unitCode: (string) $request->input('unit_code'),
            customerName: (string) $request->input('customer_name'),
            image: $request->file('image'),
            latitude: (float) $request->input('latitude'),
            longitude: (float) $request->input('longitude')
        );
    }

    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'project_id' => $this->projectId,
            'unit_code' => $this->unitCode,
            'customer_name' => $this->customerName,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
        ];
    }
}
