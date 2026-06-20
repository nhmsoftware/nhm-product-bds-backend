<?php

namespace App\Filament\Resources\CourseLessonResource\Pages;

use App\Filament\Resources\CourseLessonResource;
use App\Jobs\ExtractVideoDurationJob;
use App\Modules\Learning\Models\CourseLesson;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCourseLesson extends EditRecord
{
    protected static string $resource = CourseLessonResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function afterSave(): void
    {
        /** @var CourseLesson $lesson */
        $lesson = $this->record;

        if (!blank($lesson->video_url) && $lesson->wasChanged('video_url')) {
            ExtractVideoDurationJob::dispatch($lesson->id);
        }
    }
}
