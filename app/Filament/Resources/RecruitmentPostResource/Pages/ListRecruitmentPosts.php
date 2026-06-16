<?php
namespace App\Filament\Resources\RecruitmentPostResource\Pages;
use App\Filament\Resources\RecruitmentPostResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
class ListRecruitmentPosts extends ListRecords { protected static string $resource = RecruitmentPostResource::class; protected function getHeaderActions(): array { return [Actions\CreateAction::make()]; } }
