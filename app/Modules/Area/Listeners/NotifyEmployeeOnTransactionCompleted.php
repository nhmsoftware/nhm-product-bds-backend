<?php

declare(strict_types=1);

namespace App\Modules\Area\Listeners;

use App\Modules\Area\Events\TransactionCompleted;
use Illuminate\Contracts\Queue\ShouldQueue;

final class NotifyEmployeeOnTransactionCompleted implements ShouldQueue
{
    /**
     * Handle the event.
     *
     * @param TransactionCompleted $event
     * @return void
     */
    public function handle(TransactionCompleted $event): void
    {
        $depositRequest = $event->depositRequest;
        $employeeId = $depositRequest->user_id;

        // Implement logic to send FCM/Socket.io notification here
        // Example:
        // $this->notificationService->sendToUser($employeeId, 'Giao dịch lô đất đã công chứng thành công! Bạn được cộng 10 điểm thưởng và 1 sao thành tích.', ...);
    }
}
