<?php
namespace App\Filament\Resources\LotDepositRequestResource\Pages;
use App\Filament\Resources\LotDepositRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
class EditLotDepositRequest extends EditRecord { protected static string $resource = LotDepositRequestResource::class; protected function getHeaderActions(): array { return [Actions\DeleteAction::make()]; } }
