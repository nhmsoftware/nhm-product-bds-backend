<?php

namespace App\Modules\News\DTO;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

final class UpdateInternalPostDTO
{
    public function __construct(
        public readonly string $id,
        public readonly string $userId,
        public readonly ?string $title,
        public readonly string $content,
        public readonly ?UploadedFile $thumbnailFile,
        public readonly ?string $thumbnailUrl,
        /** @var array<int, UploadedFile> */
        public readonly array $attachmentFiles = [],
        public readonly ?array $keepAttachments = null,
    ) {
    }

    public static function fromRequest(Request $request, string $id, string $userId): self
    {
        return new self(
            id: $id,
            userId: $userId,
            title: $request->input('title'),
            content: $request->input('content', ''),
            thumbnailFile: $request->file('thumbnail'),
            thumbnailUrl: $request->input('thumbnail_url'),
            attachmentFiles: array_values($request->file('attachments', [])),
            keepAttachments: $request->filled('keep_attachments')
                ? json_decode((string) $request->input('keep_attachments'), true)
                : null,
        );
    }
}
