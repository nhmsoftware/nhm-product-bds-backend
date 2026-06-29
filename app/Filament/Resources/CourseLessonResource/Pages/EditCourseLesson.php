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

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['attachments'] = collect($data['attachments'] ?? [])
            ->map(function (array $item): array {
                $fileState = $item['_file_upload'] ?? null;

                if ($fileState !== null) {
                    $path = is_array($fileState)
                        ? (array_values(array_filter($fileState))[0] ?? null)
                        : (is_string($fileState) && $fileState !== '' ? $fileState : null);

                    if ($path) {
                        $item['url'] = str_starts_with($path, '/storage/') || str_starts_with($path, 'http')
                            ? $path
                            : '/storage/' . ltrim($path, '/');
                    }
                }

                unset($item['_file_upload']);
                return $item;
            })
            ->values()
            ->toArray();

        return $data;
    }

    protected function afterSave(): void
    {
        /** @var CourseLesson $lesson */
        $lesson = $this->record;

        if (!blank($lesson->video_url) && $lesson->wasChanged('video_url')) {
            ExtractVideoDurationJob::dispatchSync($lesson->id);
        }
    }
}
