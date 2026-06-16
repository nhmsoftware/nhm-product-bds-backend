<?php
namespace App\Filament\Resources\DepartmentTransferRequestResource\Pages;
use App\Filament\Resources\DepartmentTransferRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
class ListDepartmentTransferRequests extends ListRecords { protected static string $resource = DepartmentTransferRequestResource::class; protected function getHeaderActions(): array { return [Actions\CreateAction::make()]; } }
