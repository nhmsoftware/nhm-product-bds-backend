<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\DTO;

use Illuminate\Http\Request;

final class ViewRevenueReportDTO
{
    public function __construct(
        public readonly string $userId,
        public readonly ?string $startDate = null,
        public readonly ?string $endDate = null,
        public readonly ?string $department = null,
        public readonly ?string $projectId = null,
        public readonly ?string $area = null
    ) {
    }

    public static function fromRequest(Request $request): self
    {
        return new self(
            userId: (string) $request->user()?->id,
            startDate: $request->query('start_date'),
            endDate: $request->query('end_date'),
            department: $request->query('department'),
            projectId: $request->query('project_id'),
            area: $request->query('area')
        );
    }
}
