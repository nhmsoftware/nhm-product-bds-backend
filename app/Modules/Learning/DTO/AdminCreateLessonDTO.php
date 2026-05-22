<?php

namespace App\Modules\Learning\DTO;

use Illuminate\Http\Request;

final class AdminCreateLessonDTO
{
    public function __construct(
        public readonly string $courseId,
        public readonly string $title,
        public readonly ?string $content,
        public readonly ?string $videoUrl,
        public readonly int $durationMinutes,
        public readonly int $order,
        public readonly bool $isActive,
        public readonly ?array $attachments,
    ) {
    }

    public static function fromRequest(Request $request): self
    {
        return new self(
            courseId: $request->input('course_id'),
            title: $request->input('title'),
            content: $request->input('content'),
            videoUrl: $request->input('video_url'),
            durationMinutes: (int) $request->input('duration_minutes', 0),
            order: (int) $request->input('order', 0),
            isActive: $request->boolean('is_active', true),
            attachments: $request->input('attachments'),
        );
    }

    public function toArray(): array
    {
        return [
            'course_id' => $this->courseId,
            'title' => $this->title,
            'content' => $this->content,
            'video_url' => $this->videoUrl,
            'duration_minutes' => $this->durationMinutes,
            'order' => $this->order,
            'is_active' => $this->isActive,
            'attachments' => $this->attachments,
        ];
    }
}
