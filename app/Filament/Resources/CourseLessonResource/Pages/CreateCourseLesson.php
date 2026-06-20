<?php

namespace App\Filament\Resources\CourseLessonResource\Pages;

use App\Filament\Resources\CourseLessonResource;
use App\Jobs\ExtractVideoDurationJob;
use App\Modules\Learning\Models\CourseLesson;
use Filament\Resources\Pages\CreateRecord;

class CreateCourseLesson extends CreateRecord
{
    protected static string $resource = CourseLessonResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function afterCreate(): void
    {
        /** @var CourseLesson $lesson */
        $lesson = $this->record;

        if (!blank($lesson->video_url)) {
            ExtractVideoDurationJob::dispatch($lesson->id);
        }
    }
}
