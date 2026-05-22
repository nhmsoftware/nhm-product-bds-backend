<?php

namespace App\Modules\Learning\DTO;

use Illuminate\Http\Request;

final class AdminViewCoursesDTO
{
    public function __construct(
        public readonly ?string $search,
        public readonly ?bool $isActive,
        public readonly ?bool $isRequired,
        public readonly ?string $department,
        public readonly ?string $jobPosition,
        public readonly int $perPage,
        public readonly int $page,
    ) {
    }

    public static function fromRequest(Request $request): self
    {
        return new self(
            search: $request->query('search'),
            isActive: $request->has('is_active') ? filter_var($request->query('is_active'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) : null,
            isRequired: $request->has('is_required') ? filter_var($request->query('is_required'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) : null,
            department: $request->query('department'),
            jobPosition: $request->query('job_position'),
            perPage: (int) $request->query('per_page', 10),
            page: (int) $request->query('page', 1),
        );
    }

    public function toArray(): array
    {
        return [
            'search' => $this->search,
            'is_active' => $this->isActive,
            'is_required' => $this->isRequired,
            'department' => $this->department,
            'job_position' => $this->jobPosition,
            'per_page' => $this->perPage,
            'page' => $this->page,
        ];
    }
}
