<?php
namespace App\Filament\Resources\ConsultationMessageResource\Pages;
use App\Filament\Resources\ConsultationMessageResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
class ListConsultationMessages extends ListRecords { protected static string $resource = ConsultationMessageResource::class; protected function getHeaderActions(): array { return []; } }
