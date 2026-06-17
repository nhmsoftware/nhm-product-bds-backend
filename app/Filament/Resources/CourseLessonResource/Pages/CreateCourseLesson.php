<?php
namespace App\Filament\Resources\CourseLessonResource\Pages;
use App\Filament\Resources\CourseLessonResource;
use Filament\Resources\Pages\CreateRecord;
class CreateCourseLesson extends CreateRecord { protected static string $resource = CourseLessonResource::class; 
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
