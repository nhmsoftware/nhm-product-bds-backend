<?php
namespace App\Filament\Resources\RewardPointHistoryResource\Pages;
use App\Filament\Resources\RewardPointHistoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
class ListRewardPointHistorys extends ListRecords { protected static string $resource = RewardPointHistoryResource::class; protected function getHeaderActions(): array { return [Actions\CreateAction::make()]; } }
