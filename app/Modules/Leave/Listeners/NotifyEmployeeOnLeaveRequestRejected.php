<?php

declare(strict_types=1);

namespace App\Modules\Leave\Listeners;

use App\Modules\Leave\Events\LeaveRequestRejected;
use App\Modules\Notification\Interfaces\NotificationRepositoryInterface;
use Illuminate\Contracts\Queue\ShouldQueue;

final class NotifyEmployeeOnLeaveRequestRejected implements ShouldQueue
{
    public function __construct(
        private readonly NotificationRepositoryInterface $notificationRepository,
    ) {
    }

    public function handle(LeaveRequestRejected $event): void
    {
        $leaveRequest = $event->leaveRequest;
        $employee = $leaveRequest->user;

        if (!$employee) {
            return;
        }

        $startDate = $leaveRequest->start_date;
        $endDate = $leaveRequest->end_date;

        $this->notificationRepository->createForUser('leave_request_rejected', (string) $employee->id, [
            'title' => 'Đơn nghỉ phép bị từ chối',
            'body' => "Đơn nghỉ phép từ {$startDate} đến {$endDate} đã bị từ chối.",
            'user_id' => (string) $employee->id,
            'notifiable_id' => (string) $employee->id,
            'action_type' => 'leave_request',
            'action_id' => (string) $leaveRequest->id,
        ]);
    }
}
