<?php

declare(strict_types=1);

namespace App\Modules\Area\DTO;

use Illuminate\Http\Request;

final class CreateLotDepositRequestDTO
{
    public function __construct(
        public readonly string $lot_id,
        public readonly string $user_id,
        public readonly ?string $reason
    ) {
    }

    public static function fromRequest(Request $request, string $lotId): self
    {
        return new self(
            lot_id: $lotId,
            user_id: $request->user()->id,
            reason: $request->input('reason')
        );
    }

    public function toArray(): array
    {
        return [
            'lot_id' => $this->lot_id,
            'user_id' => $this->user_id,
            'reason' => $this->reason,
        ];
    }
}
