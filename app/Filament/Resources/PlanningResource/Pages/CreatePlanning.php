<?php
namespace App\Filament\Resources\PlanningResource\Pages;
use App\Filament\Resources\PlanningResource;
use Filament\Resources\Pages\CreateRecord;
class CreatePlanning extends CreateRecord { protected static string $resource = PlanningResource::class; 
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
