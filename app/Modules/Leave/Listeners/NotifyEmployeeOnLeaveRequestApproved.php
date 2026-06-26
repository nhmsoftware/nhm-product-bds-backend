<?php

declare(strict_types=1);

namespace App\Modules\Leave\Listeners;

use App\Modules\Leave\Events\LeaveRequestApproved;
use App\Modules\Notification\Interfaces\NotificationRepositoryInterface;
use Illuminate\Contracts\Queue\ShouldQueue;

final class NotifyEmployeeOnLeaveRequestApproved implements ShouldQueue
{
    public function __construct(
        private readonly NotificationRepositoryInterface $notificationRepository,
    ) {
    }

    public function handle(LeaveRequestApproved $event): void
    {
        $leaveRequest = $event->leaveRequest;
        $employee = $leaveRequest->user;

        if (!$employee) {
            return;
        }

        $startDate = $leaveRequest->start_date;
        $endDate = $leaveRequest->end_date;

        $this->notificationRepository->createForUser('leave_request_approved', (string) $employee->id, [
            'title' => 'Đơn nghỉ phép đã được duyệt',
            'body' => "Đơn nghỉ phép từ {$startDate} đến {$endDate} đã được duyệt.",
            'user_id' => (string) $employee->id,
            'notifiable_id' => (string) $employee->id,
            'action_type' => 'leave_request',
            'action_id' => (string) $leaveRequest->id,
        ]);
    }
}
