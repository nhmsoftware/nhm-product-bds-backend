<?php
namespace App\Filament\Resources\ConsultationSettingResource\Pages;
use App\Filament\Resources\ConsultationSettingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
class ListConsultationSettings extends ListRecords { protected static string $resource = ConsultationSettingResource::class; protected function getHeaderActions(): array { return [Actions\CreateAction::make()]; } }
