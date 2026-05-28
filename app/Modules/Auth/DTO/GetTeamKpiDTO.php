<?php

declare(strict_types=1);

namespace App\Modules\Auth\DTO;

use Illuminate\Http\Request;

final class GetTeamKpiDTO
{
    public function __construct(
        public readonly string $userId,
        public readonly ?string $search,
        public readonly ?string $jobPosition,
        public readonly ?string $fromDate,
        public readonly ?string $toDate,
        public readonly int $perPage = 15,
    ) {
    }

    /**
     * Map request data to DTO
     *
     * @param Request $request
     * @return self
     */
    public static function fromRequest(Request $request): self
    {
        return new self(
            userId: (string) $request->user()->id,
            search: $request->query('search'),
            jobPosition: $request->query('job_position'),
            fromDate: $request->query('from_date'),
            toDate: $request->query('to_date'),
            perPage: (int) $request->query('per_page', 15),
        );
    }
}
