<?php

namespace App\Filament\Resources\LegalTopicResource\Pages;

use App\Filament\Resources\LegalTopicResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLegalTopics extends ListRecords
{
    protected static string $resource = LegalTopicResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Tạo chủ đề pháp lý'),
        ];
    }
}
