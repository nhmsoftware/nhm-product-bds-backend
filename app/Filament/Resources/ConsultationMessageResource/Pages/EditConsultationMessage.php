<?php
namespace App\Filament\Resources\ConsultationMessageResource\Pages;
use App\Filament\Resources\ConsultationMessageResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
class EditConsultationMessage extends EditRecord { protected static string $resource = ConsultationMessageResource::class; protected function getHeaderActions(): array { return [Actions\DeleteAction::make()]; } 
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
