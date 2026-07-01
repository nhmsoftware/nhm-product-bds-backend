<?php

namespace App\Filament\Resources\RoleResource\Pages;

use App\Filament\Resources\RoleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRole extends EditRecord
{
    protected static string $resource = RoleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->visible(fn () => !$this->record->is_system),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function afterSave(): void
    {
        $role = $this->record;
        $data = $this->form->getRawState();
        
        $permIds = [];
        if ($data['manage_all'] ?? false) {
            $manageAllPerm = \App\Modules\Auth\Models\Permission::where('name', 'manage_all')->first();
            if ($manageAllPerm) {
                $permIds[] = $manageAllPerm->id;
            }
        } else {
            $permIds = $data['permissions'] ?? [];
        }
        
        $role->permissions()->sync($permIds);
    }
}
