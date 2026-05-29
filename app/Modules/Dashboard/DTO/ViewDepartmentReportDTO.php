<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\DTO;

use Illuminate\Http\Request;

final class ViewDepartmentReportDTO
{
    public function __construct(
        public readonly string $userId,
        public readonly ?string $department = null,
        public readonly ?string $startDate = null,
        public readonly ?string $endDate = null
    ) {
    }

    public static function fromRequest(Request $request): self
    {
        return new self(
            userId: (string) $request->user()?->id,
            department: $request->query('department'),
            startDate: $request->query('start_date'),
            endDate: $request->query('end_date')
        );
    }
}
