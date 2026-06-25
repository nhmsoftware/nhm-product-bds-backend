<?php

namespace App\Modules\Learning\DTO;

use Illuminate\Http\Request;

final class AdminUpdateCourseDTO
{
    public function __construct(
        public readonly ?string $title,
        public readonly ?string $description,
        public readonly ?string $thumbnail,
        public readonly ?bool $isRequired,
        public readonly ?array $allowedRoles,
        public readonly ?string $department,
        public readonly ?string $jobPosition,
        public readonly ?int $order,
        public readonly ?bool $isActive,
        public readonly ?bool $hasCertificate,
        public readonly array $lessons,
    ) {
    }

    public static function fromRequest(Request $request): self
    {
        return new self(
            title: $request->input('title'),
            description: $request->input('description'),
            thumbnail: $request->input('thumbnail'),
            isRequired: $request->has('is_required') ? $request->boolean('is_required') : null,
            allowedRoles: $request->has('allowed_roles') ? $request->input('allowed_roles') : null,
            department: $request->input('department'),
            jobPosition: $request->input('job_position'),
            order: $request->has('order') ? (int) $request->input('order') : null,
            isActive: $request->has('is_active') ? $request->boolean('is_active') : null,
            hasCertificate: $request->has('has_certificate') ? $request->boolean('has_certificate') : null,
            lessons: $request->input('lessons') ?? [],
        );
    }

    public function toArray(): array
    {
        $data = [];
        if ($this->title !== null) $data['title'] = $this->title;
        if ($this->description !== null) $data['description'] = $this->description;
        if ($this->thumbnail !== null) $data['thumbnail'] = $this->thumbnail;
        if ($this->isRequired !== null) $data['is_required'] = $this->isRequired;
        if ($this->allowedRoles !== null) $data['allowed_roles'] = $this->allowedRoles ?: null;
        if ($this->department !== null) $data['department'] = $this->department;
        if ($this->jobPosition !== null) $data['job_position'] = $this->jobPosition;
        if ($this->order !== null) $data['order'] = $this->order;
        if ($this->isActive !== null) $data['is_active'] = $this->isActive;
        if ($this->hasCertificate !== null) $data['has_certificate'] = $this->hasCertificate;
        return $data;
    }
}
