<?php

declare(strict_types=1);

namespace App\Modules\Project\Repositories;

use App\Core\Repository\BaseRepository;
use App\Modules\Project\Interfaces\ProjectAssignmentRepositoryInterface;
use App\Modules\Project\Models\ProjectAssignment;

class ProjectAssignmentRepository extends BaseRepository implements ProjectAssignmentRepositoryInterface
{
    public function getModel(): string
    {
        return ProjectAssignment::class;
    }

    public function findAssignment(string $projectId, string $assignableId, string $assignableType): ?ProjectAssignment
    {
        return $this->model->where('project_id', $projectId)
            ->where('assignable_id', $assignableId)
            ->where('assignable_type', $assignableType)
            ->first();
    }

    /**
     * Kiểm tra phòng ban có tồn tại hay không.
     *
     * @param string $id
     * @return bool
     */
    public function checkActiveDepartmentExists(string $id): bool
    {
        return \Illuminate\Support\Facades\DB::table('departments')
            ->where('id', $id)
            ->whereNull('deleted_at')
            ->exists();
    }
}
