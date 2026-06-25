<?php

namespace App\Filament\Resources\RecruitmentApplicationResource\Pages;

use App\Filament\Resources\RecruitmentApplicationResource;
use Filament\Resources\Pages\ListRecords;

class ListRecruitmentApplications extends ListRecords
{
    protected static string $resource = RecruitmentApplicationResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
