<?php
namespace App\Filament\Resources\LotDepositRequestResource\Pages;
use App\Filament\Resources\LotDepositRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
class ListLotDepositRequests extends ListRecords { protected static string $resource = LotDepositRequestResource::class; protected function getHeaderActions(): array { return [Actions\CreateAction::make()]; } }
