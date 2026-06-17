<?php
namespace App\Filament\Resources\AreaCommentResource\Pages;
use App\Filament\Resources\AreaCommentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
class EditAreaComment extends EditRecord { protected static string $resource = AreaCommentResource::class; protected function getHeaderActions(): array { return [Actions\DeleteAction::make()]; } 
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
