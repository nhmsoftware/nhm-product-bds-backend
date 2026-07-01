<?php

namespace App\Filament\Resources\NotificationResource\Pages;

use App\Filament\Resources\NotificationResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;
use App\Modules\Auth\Models\User;
use App\Modules\Auth\Models\Role;

class CreateNotification extends CreateRecord
{
    protected static string $resource = NotificationResource::class;

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        $usersQuery = User::query()
            ->where('is_active', true)
            ->whereHas('role', fn($q) => $q->where('name', '!=', 'buyer'))
            ->where(fn ($query) => $query->whereHas('role', fn($q) => $q->where('name', '!=', 'employee'))->orWhere(fn ($sub) => $sub->whereNotNull('job_position_id')));

        if ($data['target_type'] === 'role') {
            $role = Role::where('name', $data['target_role'])->first();
            if ($role) {
                $usersQuery->where('role_id', $role->id);
            }
        } elseif ($data['target_type'] === 'department') {
            $usersQuery->where('department_id', $data['target_department']);
        } elseif ($data['target_type'] === 'area') {
            // target_area lưu branch_id (UUID)
            $usersQuery->where('branch_id', $data['target_area']);
        } elseif ($data['target_type'] === 'users') {
            $usersQuery->whereIn('id', $data['target_users']);
        }

        $recipientIds = $usersQuery->pluck('id')->toArray();

        if (empty($recipientIds)) {
            throw ValidationException::withMessages([
                'target_type' => 'Không tìm thấy người nhận nào phù hợp với bộ lọc đã chọn.',
            ]);
        }

        $targetLabel = match ($data['target_type']) {
            'all' => 'Tất cả nhân sự',
            'role' => 'Vai trò: ' . (Role::where('name', $data['target_role'])->first()?->display_name ?? $data['target_role']),
            'department' => 'Phòng ban: ' . (function () use ($data) {
                $dept = \App\Modules\Auth\Models\Department::with('branch')->find($data['target_department']);
                return $dept ? "{$dept->name} ({$dept->branch?->name})" : 'Không xác định';
            })(),
            'area' => 'Chi nhánh: ' . (\App\Modules\Branch\Models\Branch::find($data['target_area'])?->name ?? 'Không xác định'),
            'users' => 'Đích danh (' . count($recipientIds) . ' nhân sự)',
            default => 'Không xác định',
        };

        $groupId = \Illuminate\Support\Str::uuid()->toString();
        $firstNotification = null;
        $repo = app(\App\Modules\Notification\Interfaces\NotificationRepositoryInterface::class);

        foreach ($recipientIds as $index => $userId) {
            $notification = $repo->createForUser('admin_notification', (string) $userId, [
                'title' => $data['title'],
                'body' => $data['body'],
                'group_id' => $groupId,
                'target_type' => $data['target_type'],
                'target_label' => $targetLabel,
            ]);
            if ($index === 0) {
                $firstNotification = $notification;
            }
        }

        return $firstNotification;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
