<?php

namespace App\Filament\Resources\NewsResource\Pages;

use App\Filament\Resources\NewsResource;
use Filament\Resources\Pages\CreateRecord;

class CreateNews extends CreateRecord
{
    protected static string $resource = NewsResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
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
