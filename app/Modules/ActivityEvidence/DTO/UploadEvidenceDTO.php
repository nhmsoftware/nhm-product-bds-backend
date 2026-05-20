<?php

namespace App\Modules\ActivityEvidence\DTO;

use App\Modules\ActivityEvidence\Http\Requests\UploadEvidenceRequest;
use Illuminate\Http\UploadedFile;

final class UploadEvidenceDTO
{
    public function __construct(
        public readonly string $userId,
        public readonly UploadedFile $image
    ) {
    }

    public static function fromRequest(UploadEvidenceRequest $request, string $userId): self
    {
        return new self(
            userId: $userId,
            image: $request->file('image')
        );
    }
}
