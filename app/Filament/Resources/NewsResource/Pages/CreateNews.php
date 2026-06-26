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
        if (empty($data['branch_id'])) {
            $data['department'] = null;
        }

        if (($data['category'] ?? '') !== 'internal') {
            $data['branch_id']  = null;
            $data['department'] = null;
        }

        if (!empty($data['is_published'])) {
            $data['published_at'] = now();
        }

        return $data;
    }
}
