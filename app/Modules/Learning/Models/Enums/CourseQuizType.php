<?php

declare(strict_types=1);

namespace App\Modules\Learning\Models\Enums;

enum CourseQuizType: string
{
    case MULTIPLE_CHOICE = 'multiple_choice';
    case ESSAY = 'essay';

    public function label(): string
    {
        return match ($this) {
            self::MULTIPLE_CHOICE => 'Trắc nghiệm',
            self::ESSAY => 'Tự luận',
        };
    }
}
