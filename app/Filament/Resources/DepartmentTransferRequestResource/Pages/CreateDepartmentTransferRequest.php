<?php
namespace App\Filament\Resources\DepartmentTransferRequestResource\Pages;
use App\Filament\Resources\DepartmentTransferRequestResource;
use Filament\Resources\Pages\CreateRecord;
class CreateDepartmentTransferRequest extends CreateRecord { protected static string $resource = DepartmentTransferRequestResource::class; 
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
