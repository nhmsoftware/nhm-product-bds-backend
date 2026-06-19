<?php

namespace App\Filament\Resources\CourseResource\Pages;

use App\Filament\Resources\CourseResource;
use App\Modules\Learning\Models\Course;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateCourse extends CreateRecord
{
    protected static string $resource = CourseResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function beforeCreate(): void
    {
        if (!($this->data['is_required'] ?? false)) {
            return;
        }

        if (Course::where('is_required', true)->exists()) {
            Notification::make()
                ->title('Chỉ được có 1 khóa học bắt buộc')
                ->body('Vui lòng bỏ đánh dấu bắt buộc ở khóa học hiện tại trước khi tạo khóa học bắt buộc mới.')
                ->danger()
                ->send();

            $this->halt();
        }
    }
}
