<?php
namespace App\Filament\Resources\DepartmentTransferRequestResource\Pages;
use App\Filament\Resources\DepartmentTransferRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
class EditDepartmentTransferRequest extends EditRecord { protected static string $resource = DepartmentTransferRequestResource::class; protected function getHeaderActions(): array { return [Actions\DeleteAction::make()]; } }
