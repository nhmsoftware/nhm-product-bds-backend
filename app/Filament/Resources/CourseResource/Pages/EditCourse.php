<?php

namespace App\Filament\Resources\CourseResource\Pages;

use App\Filament\Resources\CourseResource;
use App\Modules\Learning\Models\Course;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditCourse extends EditRecord
{
    protected static string $resource = CourseResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function beforeSave(): void
    {
        if (!($this->data['is_required'] ?? false)) {
            return;
        }

        $alreadyExists = Course::where('is_required', true)
            ->where('id', '!=', $this->record->id)
            ->exists();

        if ($alreadyExists) {
            Notification::make()
                ->title('Chỉ được có 1 khóa học bắt buộc')
                ->body('Vui lòng bỏ đánh dấu bắt buộc ở khóa học hiện tại trước khi đặt khóa học này là bắt buộc.')
                ->danger()
                ->send();

            $this->halt();
        }
    }
}
