<?php

namespace App\Filament\Resources\ConsultationSettingResource\Pages;

use App\Filament\Resources\ConsultationSettingResource;
use App\Modules\Consultation\Models\ConsultationSetting;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditConsultationSetting extends EditRecord
{
    protected static string $resource = ConsultationSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Nếu bật is_active cho cấu hình đang chỉnh sửa, tắt tất cả cấu hình khác
        if (!empty($data['is_active'])) {
            ConsultationSetting::where('id', '!=', $this->record->id)
                ->where('is_active', true)
                ->update(['is_active' => false]);
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
