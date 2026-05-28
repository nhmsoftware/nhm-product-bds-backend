<?php

declare(strict_types=1);

namespace App\Modules\Auth\DTO;

use Illuminate\Http\Request;

final class GetDepartmentRankingDTO
{
    public function __construct(
        public readonly string $userId,
        public readonly ?int $month,
        public readonly ?int $quarter,
        public readonly ?int $year,
        public readonly ?string $area,
        public readonly int $perPage = 15,
    ) {
    }

    /**
     * Map request to DTO
     *
     * @param Request $request
     * @return self
     */
    public static function fromRequest(Request $request): self
    {
        $month = $request->query('month');
        $quarter = $request->query('quarter');
        $year = $request->query('year');

        return new self(
            userId: (string) $request->user()->id,
            month: $month !== null ? (int) $month : null,
            quarter: $quarter !== null ? (int) $quarter : null,
            year: $year !== null ? (int) $year : null,
            area: $request->query('area'),
            perPage: (int) $request->query('per_page', 15),
        );
    }
}
