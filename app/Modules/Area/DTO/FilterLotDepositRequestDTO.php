<?php

declare(strict_types=1);

namespace App\Modules\Area\DTO;

use Illuminate\Http\Request;

final class FilterLotDepositRequestDTO
{
    public function __construct(
        public readonly ?int $status,
        public readonly ?string $project_id,
        public readonly ?string $employee_id,
        public readonly ?string $branch,
        public readonly ?string $search,
        public readonly int $per_page = 15,
        public readonly int $page = 1
    ) {
    }

    public static function fromRequest(Request $request): self
    {
        return new self(
            status: $request->input('status') !== null ? (int) $request->input('status') : null,
            project_id: $request->input('project_id'),
            employee_id: $request->input('employee_id'),
            branch: $request->input('branch'),
            search: $request->input('search'),
            per_page: (int) $request->input('per_page', 15),
            page: (int) $request->input('page', 1)
        );
    }

    public function toArray(): array
    {
        return [
            'status' => $this->status,
            'project_id' => $this->project_id,
            'employee_id' => $this->employee_id,
            'branch' => $this->branch,
            'search' => $this->search,
            'per_page' => $this->per_page,
            'page' => $this->page,
        ];
    }
}
