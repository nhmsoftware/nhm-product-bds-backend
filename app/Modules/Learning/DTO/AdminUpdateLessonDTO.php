<?php

namespace App\Modules\Learning\DTO;

use Illuminate\Http\Request;

final class AdminUpdateLessonDTO
{
    public function __construct(
        public readonly ?string $title,
        public readonly ?string $content,
        public readonly ?string $videoUrl,
        public readonly ?int $durationMinutes,
        public readonly ?int $order,
        public readonly ?bool $isActive,
        public readonly ?array $attachments,
    ) {
    }

    public static function fromRequest(Request $request): self
    {
        return new self(
            title: $request->input('title'),
            content: $request->input('content'),
            videoUrl: $request->input('video_url'),
            durationMinutes: $request->has('duration_seconds') ? (int) $request->input('duration_seconds') : null,
            order: $request->has('order') ? (int) $request->input('order') : null,
            isActive: $request->has('is_active') ? $request->boolean('is_active') : null,
            attachments: $request->input('attachments'),
        );
    }

    public function toArray(): array
    {
        $data = [];
        if ($this->title !== null) $data['title'] = $this->title;
        if ($this->content !== null) $data['content'] = $this->content;
        if ($this->videoUrl !== null) $data['video_url'] = $this->videoUrl;
        if ($this->durationMinutes !== null) $data['duration_seconds'] = $this->durationMinutes;
        if ($this->order !== null) $data['order'] = $this->order;
        if ($this->isActive !== null) $data['is_active'] = $this->isActive;
        if ($this->attachments !== null) $data['attachments'] = $this->attachments;
        return $data;
    }
}
