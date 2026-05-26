<?php

declare(strict_types=1);

namespace App\Modules\Project\Interfaces;

use App\Core\Interfaces\BaseRepositoryInterface;

interface ProjectAssignmentRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Tìm phân quyền theo project_id, assignable_id và assignable_type.
     *
     * @param string $projectId
     * @param string $assignableId
     * @param string $assignableType
     * @return \App\Modules\Project\Models\ProjectAssignment|null
     */
    public function findAssignment(string $projectId, string $assignableId, string $assignableType): ?\App\Modules\Project\Models\ProjectAssignment;
}
