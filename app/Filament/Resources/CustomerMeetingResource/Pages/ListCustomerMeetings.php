<?php
namespace App\Filament\Resources\CustomerMeetingResource\Pages;
use App\Filament\Resources\CustomerMeetingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
class ListCustomerMeetings extends ListRecords { protected static string $resource = CustomerMeetingResource::class; protected function getHeaderActions(): array { return [Actions\CreateAction::make()]; } }
