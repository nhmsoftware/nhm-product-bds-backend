<?php

declare(strict_types=1);

namespace App\Modules\Area\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Modules\Area\Events\LotDepositRequested;
use Illuminate\Support\Facades\Log;

class NotifyAdminOnLotDepositRequested implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(LotDepositRequested $event): void
    {
        $depositRequest = $event->depositRequest;
        
        // Push notification logic would go here
        // Ex: Send notification to admins about new deposit request for $depositRequest->lot_id

        Log::info("Lot deposit requested for lot {$depositRequest->lot_id} by user {$depositRequest->user_id}");
    }
}
