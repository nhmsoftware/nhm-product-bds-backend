<?php
namespace App\Filament\Resources\CustomerMeetingResource\Pages;
use App\Filament\Resources\CustomerMeetingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
class EditCustomerMeeting extends EditRecord { protected static string $resource = CustomerMeetingResource::class; protected function getHeaderActions(): array { return [Actions\DeleteAction::make()]; } 
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
