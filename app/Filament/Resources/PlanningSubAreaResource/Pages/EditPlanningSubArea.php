<?php
namespace App\Filament\Resources\PlanningSubAreaResource\Pages;
use App\Filament\Resources\PlanningSubAreaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
class EditPlanningSubArea extends EditRecord
{
    protected static string $resource = PlanningSubAreaResource::class;
    protected function getHeaderActions(): array { return [Actions\DeleteAction::make()]; }
    protected function getRedirectUrl(): string { return $this->getResource()::getUrl('index'); }
}
