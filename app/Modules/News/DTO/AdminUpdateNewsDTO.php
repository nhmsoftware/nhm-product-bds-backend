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
        public readonly ?array $contentBlocks,
        public readonly bool $hasContentBlocks,
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
        $contentBlocks = self::contentBlocksFromRequest($request);

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
            content: $request->input('content') ?? ($request->has('content_blocks') ? self::plainTextFromBlocks($contentBlocks) : null),
            contentBlocks: $contentBlocks,
            hasContentBlocks: $request->has('content_blocks'),
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

    private static function contentBlocksFromRequest(Request $request): ?array
    {
        if (!$request->has('content_blocks')) {
            return null;
        }

        $value = $request->input('content_blocks');
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            $value = is_array($decoded) ? $decoded : null;
        }

        if (!is_array($value)) {
            return null;
        }

        return collect($value)
            ->filter(fn ($block) => is_array($block))
            ->map(fn (array $block) => self::normalizeBlock($block))
            ->filter()
            ->values()
            ->all();
    }

    private static function normalizeBlock(array $block): ?array
    {
        $type = strtolower((string) ($block['type'] ?? 'paragraph'));
        if (!in_array($type, ['heading', 'paragraph', 'image', 'quote'], true)) {
            return null;
        }

        $normalized = ['type' => $type];

        if ($type === 'image') {
            $url = trim((string) ($block['url'] ?? ''));
            if ($url === '') {
                return null;
            }
            $normalized['url'] = $url;
            if (!empty($block['caption'])) {
                $normalized['caption'] = trim((string) $block['caption']);
            }
            return $normalized;
        }

        $text = trim((string) ($block['text'] ?? ''));
        if ($text === '') {
            return null;
        }
        $normalized['text'] = $text;
        if ($type === 'quote' && !empty($block['author'])) {
            $normalized['author'] = trim((string) $block['author']);
        }

        return $normalized;
    }

    private static function plainTextFromBlocks(?array $blocks): ?string
    {
        if (empty($blocks)) {
            return null;
        }

        return collect($blocks)
            ->map(fn (array $block) => $block['text'] ?? $block['caption'] ?? '')
            ->filter()
            ->implode("\n\n");
    }
}
