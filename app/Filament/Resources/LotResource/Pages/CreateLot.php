<?php
namespace App\Filament\Resources\LotResource\Pages;
use App\Filament\Resources\LotResource;
use Filament\Resources\Pages\CreateRecord;
class CreateLot extends CreateRecord { protected static string $resource = LotResource::class; 
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
