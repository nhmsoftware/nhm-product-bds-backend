<?php
namespace App\Filament\Resources\LegalVideoResource\Pages;
use App\Filament\Resources\LegalVideoResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
class ListLegalVideos extends ListRecords { protected static string $resource = LegalVideoResource::class; protected function getHeaderActions(): array { return [Actions\CreateAction::make()]; } }
