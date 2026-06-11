<?php

namespace App\Modules\News\DTO;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

final class CreateInternalPostDTO
{
    public function __construct(
        public readonly string $userId,
        public readonly ?string $title,
        public readonly string $content,
        public readonly ?UploadedFile $thumbnailFile,
        public readonly ?string $thumbnailUrl,
        /** @var array<int, UploadedFile> */
        public readonly array $attachmentFiles = [],
    ) {
    }

    public static function fromRequest(Request $request, string $userId): self
    {
        return new self(
            userId: $userId,
            title: $request->input('title'),
            content: $request->input('content', ''),
            thumbnailFile: $request->file('thumbnail'),
            thumbnailUrl: $request->input('thumbnail_url'),
            attachmentFiles: array_values($request->file('attachments', [])),
        );
    }
}
