<?php

namespace App\Filament\Resources\NotificationResource\Pages;

use App\Filament\Resources\NotificationResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;
use App\Modules\Auth\Models\User;
use App\Modules\Auth\Models\Enums\UserRole;

class CreateNotification extends CreateRecord
{
    protected static string $resource = NotificationResource::class;

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        $usersQuery = User::query()
            ->where('is_active', true)
            ->where('role', '!=', UserRole::BUYER->value)
            ->where(fn ($query) => $query->where('role', '!=', UserRole::EMPLOYEE->value)->orWhere(fn ($sub) => $sub->whereNotNull('job_position_id')));

        if ($data['target_type'] === 'role') {
            $usersQuery->where('role', (int) $data['target_role']);
        } elseif ($data['target_type'] === 'department') {
            $usersQuery->where('department_id', function ($q) use ($data) {
                $q->select('id')->from('departments')->where('name', $data['target_department']);
            });
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
            'role' => 'Vai trò: ' . (UserRole::from((int) $data['target_role'])->label()),
            'department' => 'Phòng ban: ' . $data['target_department'],
            'area' => 'Chi nhánh: ' . (\App\Modules\Branch\Models\Branch::find($data['target_area'])?->name ?? 'Không xác định'),
            'users' => 'Đích danh (' . count($recipientIds) . ' nhân sự)',
            default => 'Không xác định',
        };

        $groupId = \Illuminate\Support\Str::uuid()->toString();
        $firstNotification = null;
        $repo = app(\App\Modules\Notification\Interfaces\NotificationRepositoryInterface::class);

        foreach ($recipientIds as $index => $userId) {
            $notification = $repo->createForUser((string) $userId, [
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
