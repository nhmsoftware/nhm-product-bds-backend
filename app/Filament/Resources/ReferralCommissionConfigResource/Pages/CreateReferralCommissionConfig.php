<?php
namespace App\Filament\Resources\ReferralCommissionConfigResource\Pages;
use App\Filament\Resources\ReferralCommissionConfigResource;
use Filament\Resources\Pages\CreateRecord;
class CreateReferralCommissionConfig extends CreateRecord { protected static string $resource = ReferralCommissionConfigResource::class; 
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
