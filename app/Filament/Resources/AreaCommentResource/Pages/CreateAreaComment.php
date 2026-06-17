<?php
namespace App\Filament\Resources\AreaCommentResource\Pages;
use App\Filament\Resources\AreaCommentResource;
use Filament\Resources\Pages\CreateRecord;
class CreateAreaComment extends CreateRecord { protected static string $resource = AreaCommentResource::class; 
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
