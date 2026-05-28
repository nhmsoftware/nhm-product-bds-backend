<?php

declare(strict_types=1);

namespace App\Modules\Auth\DTO;

use Illuminate\Http\Request;

final class GetEmployeeKpiDTO
{
    public function __construct(
        public readonly string $managerId,
        public readonly string $employeeId,
        public readonly ?string $fromDate,
        public readonly ?string $toDate,
        public readonly int $perPage = 15,
    ) {
    }

    /**
     * Map request and route parameters to DTO
     *
     * @param Request $request
     * @param string $employeeId
     * @return self
     */
    public static function fromRequest(Request $request, string $employeeId): self
    {
        return new self(
            managerId: (string) $request->user()->id,
            employeeId: $employeeId,
            fromDate: $request->query('from_date'),
            toDate: $request->query('to_date'),
            perPage: (int) $request->query('per_page', 15),
        );
    }
}
