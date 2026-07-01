<?php

namespace App\Filament\Resources\CustomerResource\Pages;

use App\Filament\Resources\CustomerResource;
use App\Modules\Auth\Models\Role;
use Filament\Resources\Pages\CreateRecord;

class CreateCustomer extends CreateRecord
{
    protected static string $resource = CustomerResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['role_id'] = Role::query()->where('name', 'buyer')->value('id');
        $data['staff_code'] = null;
        $data['department_id'] = null;
        $data['job_position_id'] = null;
        $data['branch_id'] = null;

        return $data;
    }
}
