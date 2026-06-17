<?php

namespace App\Filament\Resources\InventorySettingResource\Pages;

use App\Filament\Resources\InventorySettingResource;
use Filament\Resources\Pages\EditRecord;

class EditInventorySetting extends EditRecord
{
    protected static string $resource = InventorySettingResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
