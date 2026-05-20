<?php

namespace App\Modules\DepartmentTransfer\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Modules\DepartmentTransfer\Models\DepartmentTransferRequest;

class DepartmentTransferRequestRejected
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public readonly DepartmentTransferRequest $transferRequest;

    /**
     * Khởi tạo event instance.
     *
     * @param DepartmentTransferRequest $transferRequest
     */
    public function __construct(DepartmentTransferRequest $transferRequest)
    {
        $this->transferRequest = $transferRequest;
    }
}
