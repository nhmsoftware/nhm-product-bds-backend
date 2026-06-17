<?php

namespace App\Filament\Resources\ConsultationSettingResource\Pages;

use App\Filament\Resources\ConsultationSettingResource;
use App\Modules\Consultation\Models\ConsultationSetting;
use Filament\Resources\Pages\CreateRecord;

class CreateConsultationSetting extends CreateRecord
{
    protected static string $resource = ConsultationSettingResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Nếu tạo cấu hình mới với is_active = true, tắt tất cả cấu hình đang active
        if (!empty($data['is_active'])) {
            ConsultationSetting::where('is_active', true)->update(['is_active' => false]);
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
