<?php
namespace App\Filament\Resources\LegalVideoResource\Pages;
use App\Filament\Resources\LegalVideoResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
class EditLegalVideo extends EditRecord { protected static string $resource = LegalVideoResource::class; protected function getHeaderActions(): array { return [Actions\DeleteAction::make()]; } }
