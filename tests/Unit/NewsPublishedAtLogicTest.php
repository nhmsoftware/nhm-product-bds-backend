<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Filament\Resources\NewsResource\Pages\CreateNews;
use App\Filament\Resources\NewsResource\Pages\EditNews;
use Tests\TestCase;

class NewsPublishedAtLogicTest extends TestCase
{
    private array $baseData = [
        'title'        => 'Tin test',
        'slug'         => 'tin-test',
        'category'     => 'market',
        'sort'         => 1,
        'content'      => 'Nội dung',
        'summary'      => 'Tóm tắt',
    ];

    /**
     * Tạo CreateNews page instance bằng reflection để gọi mutateFormDataBeforeCreate.
     * Tránh cần database — chỉ test logic transform data.
     */
    private function callCreateMutate(array $data): array
    {
        $page = new class {
            use \Illuminate\Foundation\Testing\Concerns\InteractsWithContainer;
        };
        // Tạo partial mock chỉ để gọi method
        $reflection = new \ReflectionClass(CreateNews::class);
        // mutateFormDataBeforeCreate là protected, dùng reflection để invoke
        $method = $reflection->getMethod('mutateFormDataBeforeCreate');
        $method->setAccessible(true);

        // Instance không cần boot — chỉ gọi method với data
        $instance = $reflection->newInstanceWithoutConstructor();

        return $method->invoke($instance, $data);
    }

    private function callEditMutate(array $data): array
    {
        $reflection = new \ReflectionClass(EditNews::class);
        $method = $reflection->getMethod('mutateFormDataBeforeSave');
        $method->setAccessible(true);

        $instance = $reflection->newInstanceWithoutConstructor();

        return $method->invoke($instance, $data);
    }

    // ── Create tests ────────────────────────────────────────────────

    public function test_create_sets_published_at_when_is_published_true(): void
    {
        $data = [...$this->baseData, 'is_published' => true];
        $result = $this->callCreateMutate($data);

        $this->assertNotNull($result['published_at'], 'published_at should be set when is_published = true');
        $this->assertTrue(
            $result['published_at']->diffInSeconds(now()) < 5,
            'published_at should be approximately now'
        );
    }

    public function test_create_keeps_published_at_null_when_is_published_false(): void
    {
        $data = [...$this->baseData, 'is_published' => false];
        $result = $this->callCreateMutate($data);

        $this->assertArrayNotHasKey('published_at', $result, 'published_at should not be set when is_published = false');
    }

    public function test_create_keeps_published_at_null_when_is_published_missing(): void
    {
        $data = $this->baseData;
        $result = $this->callCreateMutate($data);

        $this->assertArrayNotHasKey('published_at', $result);
    }

    // ── Edit tests ──────────────────────────────────────────────────

    public function test_edit_sets_published_at_when_is_published_true(): void
    {
        $data = [...$this->baseData, 'is_published' => true, 'news_type' => 'public'];
        $result = $this->callEditMutate($data);

        $this->assertNotNull($result['published_at']);
        $this->assertTrue(
            $result['published_at']->diffInSeconds(now()) < 5,
            'published_at should be approximately now'
        );
    }

    public function test_edit_keeps_published_at_null_when_is_published_false(): void
    {
        $data = [...$this->baseData, 'is_published' => false, 'news_type' => 'public'];
        $result = $this->callEditMutate($data);

        $this->assertArrayNotHasKey('published_at', $result);
    }

    // ── Internal news clears branch_id/department ───────────────────

    public function test_create_clears_branch_and_department_for_public_news(): void
    {
        $data = [
            ...$this->baseData,
            'is_published' => true,
            'category'     => 'market',
            'branch_id'    => 'some-branch-id',
            'department'   => 'Kinh doanh',
        ];
        $result = $this->callCreateMutate($data);

        $this->assertNull($result['branch_id'], 'branch_id should be null for public news');
        $this->assertNull($result['department'], 'department should be null for public news');
    }

    public function test_create_keeps_branch_when_internal(): void
    {
        $data = [
            ...$this->baseData,
            'is_published' => true,
            'category'     => 'internal',
            'branch_id'    => 'some-branch-id',
            'department'   => 'Kinh doanh',
        ];
        $result = $this->callCreateMutate($data);

        $this->assertEquals('some-branch-id', $result['branch_id']);
        $this->assertEquals('Kinh doanh', $result['department']);
    }
}
