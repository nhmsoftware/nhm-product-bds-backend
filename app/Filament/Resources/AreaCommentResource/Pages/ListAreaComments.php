<?php
namespace App\Filament\Resources\AreaCommentResource\Pages;
use App\Filament\Resources\AreaCommentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
class ListAreaComments extends ListRecords { protected static string $resource = AreaCommentResource::class; protected function getHeaderActions(): array { return []; } }
