<?php

declare(strict_types=1);

namespace App\Modules\News\DTO;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

final class AdminCreateNewsDTO
{
    public function __construct(
        public readonly string $authorId,
        public readonly string $title,
        public readonly string $slug,
        public readonly ?string $summary,
        public readonly string $content,
        public readonly ?string $thumbnail,
        public readonly string $category,
        public readonly ?string $department,
        public readonly ?string $area,
        public readonly bool $isPublished,
        public readonly bool $isFeatured
    ) {
    }

    public static function fromRequest(Request $request): self
    {
        $type = $request->input('type');
        $scope = $request->input('scope');
        $status = $request->input('status');

        $isPublished = $status === 'published';
        
        $department = null;
        if ($type === 'internal' && $scope === 'department') {
            $department = $request->input('department');
        }

        return new self(
            authorId: (string) $request->user()?->id,
            title: $request->input('title'),
            slug: Str::slug($request->input('title')),
            summary: $request->input('summary'),
            content: $request->input('content'),
            thumbnail: $request->input('thumbnail'),
            category: $request->input('category'),
            department: $department,
            area: null, // As per requirements, area is not used in the form currently
            isPublished: $isPublished,
            isFeatured: (bool) $request->input('is_featured', false)
        );
    }
}
