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
