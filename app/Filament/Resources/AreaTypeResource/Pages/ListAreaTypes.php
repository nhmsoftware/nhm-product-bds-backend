<?php

namespace App\Filament\Resources\AreaTypeResource\Pages;

use App\Filament\Resources\AreaTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAreaTypes extends ListRecords
{
    protected static string $resource = AreaTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Tạo loại hình'),
        ];
    }
}
