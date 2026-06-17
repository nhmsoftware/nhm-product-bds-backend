<?php
namespace App\Filament\Resources\EmployeeProfileResource\Pages;
use App\Filament\Resources\EmployeeProfileResource;
use Filament\Resources\Pages\CreateRecord;
class CreateEmployeeProfile extends CreateRecord { protected static string $resource = EmployeeProfileResource::class; 
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
