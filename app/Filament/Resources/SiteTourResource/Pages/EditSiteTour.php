<?php
namespace App\Filament\Resources\SiteTourResource\Pages;
use App\Filament\Resources\SiteTourResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
class EditSiteTour extends EditRecord { protected static string $resource = SiteTourResource::class; protected function getHeaderActions(): array { return [Actions\DeleteAction::make()]; } 
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
