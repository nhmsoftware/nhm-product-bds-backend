<?php

namespace App\Modules\Auth\DTO;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

final class UploadEmployeeDocumentDTO
{
    /**
     * UploadEmployeeDocumentDTO constructor.
     */
    public function __construct(
        public readonly string $userId,
        public readonly string $type,
        public readonly UploadedFile $file,
    ) {
    }

    /**
     * Create DTO from validated request payload.
     */
    public static function fromRequest(Request $request): self
    {
        return new self(
            userId: (string) auth('api')->id(),
            type: $request->validated('type'),
            file: $request->file('file')
        );
    }
}
