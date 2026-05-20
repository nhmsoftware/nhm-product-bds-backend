<?php

namespace App\Modules\DepartmentTransfer\DTO;

class StoreDepartmentTransferRequestDTO
{
    public string $userId;
    public string $currentDepartment;
    public string $targetDepartment;
    public string $reason;
    public string $desiredTransferDate;

    public function __construct(array $data)
    {
        $this->userId = $data['user_id'] ?? '';
        $this->currentDepartment = $data['current_department'] ?? '';
        $this->targetDepartment = $data['target_department'] ?? '';
        $this->reason = $data['reason'] ?? '';
        $this->desiredTransferDate = $data['desired_transfer_date'] ?? '';
    }
}
