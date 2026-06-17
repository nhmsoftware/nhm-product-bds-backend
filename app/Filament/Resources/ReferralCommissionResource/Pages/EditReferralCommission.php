<?php
namespace App\Filament\Resources\ReferralCommissionResource\Pages;
use App\Filament\Resources\ReferralCommissionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
class EditReferralCommission extends EditRecord { protected static string $resource = ReferralCommissionResource::class; protected function getHeaderActions(): array { return [Actions\DeleteAction::make()]; } 
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
