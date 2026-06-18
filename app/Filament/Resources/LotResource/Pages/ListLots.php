<?php
namespace App\Filament\Resources\LotResource\Pages;
use App\Filament\Resources\LotResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
class ListLots extends ListRecords
{
    protected static string $resource = LotResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->url(fn (): string => LotResource::getUrl('create', [
                    'area_id' => request()->input('tableFilters.area.value'),
                ])),
        ];
    }
}
