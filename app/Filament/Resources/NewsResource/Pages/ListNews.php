<?php

namespace App\Filament\Resources\NewsResource\Pages;

use App\Filament\Resources\NewsResource;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;

class ListNews extends ListRecords
{
    protected static string $resource = NewsResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }

    public function getTabs(): array
    {
        return [
            'all'      => Tab::make('Tất cả'),
            'public'   => Tab::make('Tin công khai')
                ->modifyQueryUsing(fn ($query) => $query->where('category', '!=', 'internal')),
            'internal' => Tab::make('Tin nội bộ')
                ->modifyQueryUsing(fn ($query) => $query->where('category', 'internal')),
        ];
    }
}
