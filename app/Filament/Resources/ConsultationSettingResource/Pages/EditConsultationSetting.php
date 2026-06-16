<?php
namespace App\Filament\Resources\ConsultationSettingResource\Pages;
use App\Filament\Resources\ConsultationSettingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
class EditConsultationSetting extends EditRecord { protected static string $resource = ConsultationSettingResource::class; protected function getHeaderActions(): array { return [Actions\DeleteAction::make()]; } }
