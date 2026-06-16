<?php
namespace App\Filament\Resources\LotLockRequestResource\Pages;
use App\Filament\Resources\LotLockRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
class ListLotLockRequests extends ListRecords { protected static string $resource = LotLockRequestResource::class; protected function getHeaderActions(): array { return [Actions\CreateAction::make()]; } }
