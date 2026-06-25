<?php
namespace App\Filament\Resources\PlanningSubAreaResource\Pages;
use App\Filament\Resources\PlanningSubAreaResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
class ListPlanningSubAreas extends ListRecords
{
    protected static string $resource = PlanningSubAreaResource::class;
    protected function getHeaderActions(): array { return [Actions\CreateAction::make()]; }
}
