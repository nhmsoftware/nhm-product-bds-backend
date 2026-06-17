<?php
namespace App\Filament\Resources\NewsResource\Pages;
use App\Filament\Resources\NewsResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
class ListNews extends ListRecords {
    protected static string $resource = NewsResource::class;
    protected function getHeaderActions(): array { return [Actions\CreateAction::make()]; }
    
    public function getTabs(): array
    {
        return [
            'all' => \Filament\Resources\Components\Tab::make('Tất cả'),
            'public' => \Filament\Resources\Components\Tab::make('Tin công khai')
                ->modifyQueryUsing(fn ($query) => $query->where('category', '!=', 'company')),
            'internal' => \Filament\Resources\Components\Tab::make('Tin nội bộ')
                ->modifyQueryUsing(fn ($query) => $query->where('category', 'company')),
        ];
    }
}
