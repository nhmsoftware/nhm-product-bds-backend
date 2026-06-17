<?php
namespace App\Filament\Resources\CustomerMeetingResource\Pages;
use App\Filament\Resources\CustomerMeetingResource;
use Filament\Resources\Pages\CreateRecord;
class CreateCustomerMeeting extends CreateRecord { protected static string $resource = CustomerMeetingResource::class; 
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
