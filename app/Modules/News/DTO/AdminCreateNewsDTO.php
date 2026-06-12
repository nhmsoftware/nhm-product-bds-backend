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
        public readonly ?array $contentBlocks,
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
        $contentBlocks = self::contentBlocksFromRequest($request);
        
        $department = null;
        if ($type === 'internal' && $scope === 'department') {
            $department = $request->input('department');
        }

        return new self(
            authorId: (string) $request->user()?->id,
            title: $request->input('title'),
            slug: Str::slug($request->input('title')),
            summary: $request->input('summary'),
            content: (string) ($request->input('content') ?: self::plainTextFromBlocks($contentBlocks)),
            contentBlocks: $contentBlocks,
            thumbnail: $request->input('thumbnail'),
            category: $request->input('category'),
            department: $department,
            area: null, // As per requirements, area is not used in the form currently
            isPublished: $isPublished,
            isFeatured: (bool) $request->input('is_featured', false)
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

    private static function plainTextFromBlocks(?array $blocks): string
    {
        if (empty($blocks)) {
            return '';
        }

        return collect($blocks)
            ->map(fn (array $block) => $block['text'] ?? $block['caption'] ?? '')
            ->filter()
            ->implode("\n\n");
    }
}
