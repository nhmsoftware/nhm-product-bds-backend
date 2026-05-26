<?php

declare(strict_types=1);

namespace App\Modules\Project\Interfaces;

use App\Core\DTOs\ServiceReturn;
use App\Modules\Project\DTO\AssignPermissionDTO;

interface ProjectAssignmentServiceInterface
{
    /**
     * [Admin] Gán quyền Inventory cho một đối tượng (User hoặc Department).
     *
     * @param string $userId
     * @param string $projectId
     * @param AssignPermissionDTO $dto
     * @return ServiceReturn
     */
    public function assignPermission(string $userId, string $projectId, AssignPermissionDTO $dto): ServiceReturn;
}
