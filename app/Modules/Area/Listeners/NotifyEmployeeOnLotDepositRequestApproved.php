<?php

declare(strict_types=1);

namespace App\Modules\Area\Listeners;

use App\Modules\Area\Events\LotDepositRequestApproved;
use Illuminate\Contracts\Queue\ShouldQueue;

final class NotifyEmployeeOnLotDepositRequestApproved implements ShouldQueue
{
    /**
     * Handle the event.
     *
     * @param LotDepositRequestApproved $event
     * @return void
     */
    public function handle(LotDepositRequestApproved $event): void
    {
        $depositRequest = $event->depositRequest;
        $employeeId = $depositRequest->user_id;

        // Implement logic to send FCM/Socket.io notification here
        // Example:
        // $this->notificationService->sendToUser($employeeId, 'Yêu cầu đặt cọc lô đất đã được duyệt', ...);
    }
}
