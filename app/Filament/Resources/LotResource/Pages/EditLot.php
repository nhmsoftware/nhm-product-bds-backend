<?php
namespace App\Filament\Resources\LotResource\Pages;
use App\Filament\Resources\LotResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
class EditLot extends EditRecord { protected static string $resource = LotResource::class; protected function getHeaderActions(): array { return [Actions\DeleteAction::make()]; } 
    protected function getRedirectUrl(): string
    {
        $areaId = $this->record->area_id ?? null;
        if ($areaId) {
            return $this->getResource()::getUrl('index', [
                'tableFilters' => [
                    'area' => [
                        'value' => $areaId,
                    ],
                ],
            ]);
        }
        return $this->getResource()::getUrl('index');
    }
}
