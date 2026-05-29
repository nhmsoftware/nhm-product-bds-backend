<?php

declare(strict_types=1);

namespace App\Modules\News\DTO;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

final class AdminUpdateNewsDTO
{
    public function __construct(
        public readonly string $id,
        public readonly ?string $title,
        public readonly ?string $slug,
        public readonly ?string $summary,
        public readonly ?string $content,
        public readonly ?string $thumbnail,
        public readonly ?string $category,
        public readonly ?string $department,
        public readonly ?string $area,
        public readonly ?bool $isPublished,
        public readonly ?bool $isFeatured,
        public readonly ?string $type,
        public readonly ?string $scope
    ) {
    }

    public static function fromRequest(Request $request, string $id): self
    {
        $title = $request->input('title');
        
        $type = $request->input('type');
        $scope = $request->input('scope');
        $status = $request->input('status');

        $isPublished = null;
        if ($request->has('status')) {
            $isPublished = $status === 'published';
        }

        $department = $request->input('department');
        $area = $request->input('area');

        if ($request->has('type')) {
            if ($type === 'public') {
                $department = null;
                $area = null;
            } elseif ($type === 'internal' && $scope === 'department') {
                $department = $request->input('department');
                $area = null; // Area is not managed by this form requirement right now
            } elseif ($type === 'internal' && $scope === 'company') {
                $department = null;
                $area = null;
            }
        }

        return new self(
            id: $id,
            title: $title,
            slug: $title ? Str::slug($title) : null,
            summary: $request->input('summary'),
            content: $request->input('content'),
            thumbnail: $request->input('thumbnail'),
            category: $request->input('category'),
            department: $department,
            area: $area,
            isPublished: $isPublished,
            isFeatured: $request->has('is_featured') ? (bool) $request->input('is_featured') : null,
            type: $type,
            scope: $scope
        );
    }
}
