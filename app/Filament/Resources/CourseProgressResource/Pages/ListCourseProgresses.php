<?php

namespace App\Filament\Resources\CourseProgressResource\Pages;

use App\Filament\Resources\CourseProgressResource;
use Filament\Resources\Pages\ListRecords;

class ListCourseProgresses extends ListRecords
{
    protected static string $resource = CourseProgressResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
