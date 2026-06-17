<?php
namespace App\Filament\Resources\ReferralCommissionResource\Pages;
use App\Filament\Resources\ReferralCommissionResource;
use Filament\Resources\Pages\CreateRecord;
class CreateReferralCommission extends CreateRecord { protected static string $resource = ReferralCommissionResource::class; 
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
