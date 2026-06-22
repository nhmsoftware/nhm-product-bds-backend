<?php

namespace App\Filament\Resources\NewsResource\Pages;

use App\Filament\Resources\NewsResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditNews extends EditRecord
{
    protected static string $resource = NewsResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['news_type'] = ($data['category'] ?? '') === 'internal' ? 'internal' : 'public';
        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Khi chi nhánh là "Tất cả" (null) thì xóa phòng ban
        if (empty($data['branch_id'])) {
            $data['department'] = null;
        }

        // Tin công khai không có chi nhánh/phòng ban
        if (($data['category'] ?? '') !== 'internal') {
            $data['branch_id']  = null;
            $data['department'] = null;
        }

        return $data;
    }
}
