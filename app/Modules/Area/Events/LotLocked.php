<?php

declare(strict_types=1);

namespace App\Modules\Area\Events;

use App\Modules\Area\Models\Lot;
use App\Modules\Area\Models\LotLockRequest;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class LotLocked
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Lot $lot,
        public readonly LotLockRequest $lockRequest
    ) {
    }
}
