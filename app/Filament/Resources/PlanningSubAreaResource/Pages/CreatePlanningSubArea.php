<?php
namespace App\Filament\Resources\PlanningSubAreaResource\Pages;
use App\Filament\Resources\PlanningSubAreaResource;
use Filament\Resources\Pages\CreateRecord;
class CreatePlanningSubArea extends CreateRecord
{
    protected static string $resource = PlanningSubAreaResource::class;
    protected function getRedirectUrl(): string { return $this->getResource()::getUrl('index'); }
}
