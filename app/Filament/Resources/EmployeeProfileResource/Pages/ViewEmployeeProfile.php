<?php

namespace App\Filament\Resources\EmployeeProfileResource\Pages;

use App\Filament\Resources\EmployeeProfileResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewEmployeeProfile extends ViewRecord
{
    protected static string $resource = EmployeeProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }

    /**
     * Inject User model fields into the form before displaying (same logic as EditRecord).
     * This populates phone, email, address, avatar, user_name from the related User.
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
}
