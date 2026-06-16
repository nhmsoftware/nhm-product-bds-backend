<?php
namespace App\Filament\Resources\LotLockRequestResource\Pages;
use App\Filament\Resources\LotLockRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
class EditLotLockRequest extends EditRecord { protected static string $resource = LotLockRequestResource::class; protected function getHeaderActions(): array { return [Actions\DeleteAction::make()]; } }
