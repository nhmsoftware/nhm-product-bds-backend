<?php

declare(strict_types=1);

namespace App\Modules\Area\Listeners;

use App\Modules\Area\Events\LotDepositRequestRejected;
use Illuminate\Contracts\Queue\ShouldQueue;

final class NotifyEmployeeOnLotDepositRequestRejected implements ShouldQueue
{
    /**
     * Handle the event.
     *
     * @param LotDepositRequestRejected $event
     * @return void
     */
    public function handle(LotDepositRequestRejected $event): void
    {
        $depositRequest = $event->depositRequest;
        $employeeId = $depositRequest->user_id;
        $reason = $depositRequest->reject_reason;

        // Implement logic to send FCM/Socket.io notification here
        // Example:
        // $this->notificationService->sendToUser($employeeId, 'Yêu cầu đặt cọc lô đất bị từ chối. Lý do: ' . $reason, ...);
    }
}
