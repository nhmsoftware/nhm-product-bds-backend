<?php

namespace App\Modules\DepartmentTransfer\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Modules\DepartmentTransfer\Models\DepartmentTransferRequest;

class DepartmentTransferRequestCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public DepartmentTransferRequest $transferRequest;

    /**
     * Create a new event instance.
     */
    public function __construct(DepartmentTransferRequest $transferRequest)
    {
        $this->transferRequest = $transferRequest;
    }
}
