<?php

namespace App\Filament\Resources\EmployeeProfileResource\Pages;

use App\Filament\Resources\EmployeeProfileResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEmployeeProfile extends EditRecord
{
    protected static string $resource = EmployeeProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    /**
     * Inject User model fields into the form before filling.
     * These fields (phone, email, address, avatar, user_name) are stored on
     * the User model, not EmployeeProfile, so we populate them manually.
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $user = $this->getRecord()->user;

        if ($user) {
            $data['user_name'] = $user->name;
            $data['phone']     = $user->phone;
            $data['email']     = $user->email;
            $data['address']   = $user->address;
            $data['avatar']    = $user->avatar;
        }

        return $data;
    }

    /**
     * Save User model fields before Filament saves EmployeeProfile.
     * Remove them from $data so they are not passed to EmployeeProfile->fill().
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $user = $this->getRecord()->user;

        if ($user) {
            $userFields = [];

            if (isset($data['user_name'])) {
                $userFields['name'] = $data['user_name'];
            }
            if (isset($data['phone'])) {
                $userFields['phone'] = $data['phone'];
            }
            if (isset($data['email'])) {
                $userFields['email'] = $data['email'];
            }
            if (array_key_exists('address', $data)) {
                $userFields['address'] = $data['address'];
            }
            if (isset($data['avatar'])) {
                $userFields['avatar'] = $data['avatar'];
            }

            if (!empty($userFields)) {
                $user->update($userFields);
            }
        }

        unset($data['user_name'], $data['phone'], $data['email'], $data['address'], $data['avatar']);

        return $data;
    }
}
