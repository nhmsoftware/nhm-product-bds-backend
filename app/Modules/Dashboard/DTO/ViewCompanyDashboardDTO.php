<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\DTO;

use Illuminate\Http\Request;

final class ViewCompanyDashboardDTO
{
    public function __construct(
        public readonly string $userId,
        public readonly ?int $month = null,
        public readonly ?int $quarter = null,
        public readonly ?int $year = null,
        public readonly ?string $area = null
    ) {
    }

    public static function fromRequest(Request $request): self
    {
        return new self(
            userId: (string) $request->user()?->id,
            month: $request->filled('month') ? (int) $request->query('month') : null,
            quarter: $request->filled('quarter') ? (int) $request->query('quarter') : null,
            year: $request->filled('year') ? (int) $request->query('year') : null,
            area: $request->query('area')
        );
    }
}
