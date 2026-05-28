<?php

declare(strict_types=1);

namespace App\Modules\Auth\DTO;

use Illuminate\Http\Request;

final class GetRewardPointHistoryDTO
{
    public function __construct(
        public readonly string $userId,
        public readonly ?string $fromDate,
        public readonly ?string $toDate,
        public readonly int $perPage = 15,
    ) {
    }

    public static function fromRequest(Request $request): self
    {
        return new self(
            userId: $request->user()->id,
            fromDate: $request->query('from_date'),
            toDate: $request->query('to_date'),
            perPage: (int) $request->query('per_page', 15),
        );
    }
}
