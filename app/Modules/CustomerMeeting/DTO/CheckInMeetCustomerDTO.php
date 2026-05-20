<?php

namespace App\Modules\CustomerMeeting\DTO;

use App\Modules\CustomerMeeting\Http\Requests\CheckInMeetCustomerRequest;
use Illuminate\Http\UploadedFile;

final class CheckInMeetCustomerDTO
{
    public function __construct(
        public readonly string $userId,
        public readonly string $customerName,
        public readonly string $customerPhone,
        public readonly string $projectId,
        public readonly UploadedFile $image,
        public readonly float $latitude,
        public readonly float $longitude
    ) {
    }

    public static function fromRequest(CheckInMeetCustomerRequest $request, string $userId): self
    {
        return new self(
            userId: $userId,
            customerName: (string) $request->input('customer_name'),
            customerPhone: (string) $request->input('customer_phone'),
            projectId: (string) $request->input('project_id'),
            image: $request->file('image'),
            latitude: (float) $request->input('latitude'),
            longitude: (float) $request->input('longitude')
        );
    }

    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'customer_name' => $this->customerName,
            'customer_phone' => $this->customerPhone,
            'project_id' => $this->projectId,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
        ];
    }
}
