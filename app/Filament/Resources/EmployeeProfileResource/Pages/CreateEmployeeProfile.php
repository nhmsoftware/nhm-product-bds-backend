<?php

namespace App\Filament\Resources\EmployeeProfileResource\Pages;

use App\Filament\Resources\EmployeeProfileResource;
use App\Modules\Auth\Models\User;
use Filament\Resources\Pages\CreateRecord;

class CreateEmployeeProfile extends CreateRecord
{
    protected static string $resource = EmployeeProfileResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    /**
     * Save User model fields (phone, email, address, avatar, name) before
     * creating the EmployeeProfile record.
     * Remove them from $data so EmployeeProfile->fill() only gets its own fields.
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data = EmployeeProfileResource::resolveAttachmentUploads($data);

        if (!empty($data['user_id'])) {
            $user = User::find($data['user_id']);

            if ($user) {
                $userFields = [];

                if (!empty($data['user_name'])) {
                    $userFields['name'] = $data['user_name'];
                }
                if (!empty($data['phone'])) {
                    $userFields['phone'] = $data['phone'];
                }
                if (!empty($data['email'])) {
                    $userFields['email'] = $data['email'];
                }
                if (array_key_exists('address', $data)) {
                    $userFields['address'] = $data['address'];
                }
                if (!empty($data['avatar'])) {
                    $userFields['avatar'] = $data['avatar'];
                }

                if (!empty($userFields)) {
                    $user->update($userFields);
                }
            }
        }

        unset($data['user_name'], $data['phone'], $data['email'], $data['address'], $data['avatar']);

        return $data;
    }
}
