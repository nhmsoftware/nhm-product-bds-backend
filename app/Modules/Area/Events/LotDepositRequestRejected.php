<?php

declare(strict_types=1);

namespace App\Modules\Area\Events;

use App\Modules\Area\Models\LotDepositRequest;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class LotDepositRequestRejected
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly LotDepositRequest $depositRequest
    ) {
    }
}
