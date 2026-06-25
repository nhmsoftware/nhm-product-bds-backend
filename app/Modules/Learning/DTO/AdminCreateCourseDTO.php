<?php

namespace App\Modules\Learning\DTO;

use Illuminate\Http\Request;

final class AdminCreateCourseDTO
{
    public function __construct(
        public readonly string $title,
        public readonly ?string $description,
        public readonly ?string $thumbnail,
        public readonly bool $isRequired,
        public readonly ?array $allowedRoles,
        public readonly ?string $department,
        public readonly ?string $jobPosition,
        public readonly int $order,
        public readonly bool $isActive,
        public readonly bool $hasCertificate,
        public readonly array $lessons,
    ) {
    }

    public static function fromRequest(Request $request): self
    {
        return new self(
            title: $request->input('title') ?? '',
            description: $request->input('description'),
            thumbnail: $request->input('thumbnail'),
            isRequired: $request->boolean('is_required', true),
            allowedRoles: $request->input('allowed_roles'),
            department: $request->input('department'),
            jobPosition: $request->input('job_position'),
            order: (int) $request->input('order', 0),
            isActive: $request->boolean('is_active', true),
            hasCertificate: $request->boolean('has_certificate', true),
            lessons: $request->input('lessons') ?? [],
        );
    }

    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'description' => $this->description,
            'thumbnail' => $this->thumbnail,
            'is_required' => $this->isRequired,
            'allowed_roles' => $this->allowedRoles ?: null,
            'department' => $this->department,
            'job_position' => $this->jobPosition,
            'order' => $this->order,
            'is_active' => $this->isActive,
            'has_certificate' => $this->hasCertificate,
        ];
    }
}
