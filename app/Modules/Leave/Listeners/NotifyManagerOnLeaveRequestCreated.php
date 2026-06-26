<?php

declare(strict_types=1);

namespace App\Modules\Leave\Listeners;

use App\Modules\Auth\Interfaces\AuthRepositoryInterface;
use App\Modules\Leave\Events\LeaveRequestCreated;
use App\Modules\Notification\Interfaces\NotificationRepositoryInterface;
use Illuminate\Contracts\Queue\ShouldQueue;

final class NotifyManagerOnLeaveRequestCreated implements ShouldQueue
{
    public function __construct(
        private readonly AuthRepositoryInterface $authRepository,
        private readonly NotificationRepositoryInterface $notificationRepository,
    ) {
    }

    public function handle(LeaveRequestCreated $event): void
    {
        $leaveRequest = $event->leaveRequest;
        $employee = $leaveRequest->user;

        if (!$employee) {
            return;
        }

        $recipients = $this->authRepository->findManagersByBranch(
            branchId: (string) $employee->branch_id,
            excludeUserId: (string) $employee->id,
        );

        $employeeName = $employee->name;
        $startDate = $leaveRequest->start_date;
        $endDate = $leaveRequest->end_date;

        foreach ($recipients as $recipient) {
            $this->notificationRepository->createForUser('leave_request_created', (string) $recipient->id, [
                'title' => 'Đơn nghỉ phép mới',
                'body' => "{$employeeName} vừa gửi đơn nghỉ phép từ {$startDate} đến {$endDate}.",
                'user_id' => (string) $recipient->id,
                'notifiable_id' => (string) $recipient->id,
                'action_type' => 'leave_request',
                'action_id' => (string) $leaveRequest->id,
            ]);
        }
    }
}
