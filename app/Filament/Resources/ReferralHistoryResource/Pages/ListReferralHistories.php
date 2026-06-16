<?php
namespace App\Filament\Resources\ReferralHistoryResource\Pages;
use App\Filament\Resources\ReferralHistoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
class ListReferralHistories extends ListRecords { protected static string $resource = ReferralHistoryResource::class; protected function getHeaderActions(): array { return [Actions\CreateAction::make()]; } }
