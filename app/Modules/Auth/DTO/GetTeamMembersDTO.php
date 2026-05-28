<?php

declare(strict_types=1);

namespace App\Modules\Auth\DTO;

use Illuminate\Http\Request;

final class GetTeamMembersDTO
{
    public function __construct(
        public readonly string $userId,
        public readonly ?string $search,
        public readonly ?string $jobPosition,
        public readonly int $perPage = 15,
    ) {
    }

    public static function fromRequest(Request $request): self
    {
        return new self(
            userId: $request->user()->id,
            search: $request->query('search'),
            jobPosition: $request->query('job_position'),
            perPage: (int) $request->query('per_page', 15),
        );
    }
}
