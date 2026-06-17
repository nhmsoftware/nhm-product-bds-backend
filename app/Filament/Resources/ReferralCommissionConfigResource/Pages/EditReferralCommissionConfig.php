<?php
namespace App\Filament\Resources\ReferralCommissionConfigResource\Pages;
use App\Filament\Resources\ReferralCommissionConfigResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
class EditReferralCommissionConfig extends EditRecord { protected static string $resource = ReferralCommissionConfigResource::class; protected function getHeaderActions(): array { return [Actions\DeleteAction::make()]; } 
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
