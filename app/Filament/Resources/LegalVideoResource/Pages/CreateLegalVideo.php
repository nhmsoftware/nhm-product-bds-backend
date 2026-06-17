<?php
namespace App\Filament\Resources\LegalVideoResource\Pages;
use App\Filament\Resources\LegalVideoResource;
use Filament\Resources\Pages\CreateRecord;
class CreateLegalVideo extends CreateRecord { protected static string $resource = LegalVideoResource::class; 
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
