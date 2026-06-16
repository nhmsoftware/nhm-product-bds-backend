<?php
namespace App\Filament\Resources\ReferralCommissionConfigResource\Pages;
use App\Filament\Resources\ReferralCommissionConfigResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
class ListReferralCommissionConfigs extends ListRecords { protected static string $resource = ReferralCommissionConfigResource::class; protected function getHeaderActions(): array { return [Actions\CreateAction::make()]; } }
