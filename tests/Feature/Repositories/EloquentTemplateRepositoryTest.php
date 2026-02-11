<?php

namespace Tests\Feature\Repositories;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

use App\Models\Template;
use App\Enums\TemplateEngine;
use App\Repositories\EloquentTemplateRepository;

class EloquentTemplateRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private EloquentTemplateRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new EloquentTemplateRepository();
    }

    public function test_paginate_returns_paginated_templates(): void
    {
        Template::factory()->count(20)->create();

        $result = $this->repository->paginate(10);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertCount(10, $result->items());
        $this->assertEquals(20, $result->total());
    }

    public function test_paginate_orders_by_latest(): void
    {
        $first = Template::factory()->create(['created_at' => now()->subMinute()]);
        $second = Template::factory()->create(['created_at' => now()]);

        $result = $this->repository->paginate();

        $this->assertEquals($second->id, $result->items()[0]->id);
    }

    public function test_create_persists_template(): void
    {
        $template = $this->repository->create([
            'name' => 'Welcome Email',
            'engine' => TemplateEngine::Blade->value,
            'subject_template' => 'Hello {{ $name }}',
            'body_content' => '<h1>Welcome</h1>',
        ]);

        $this->assertInstanceOf(Template::class, $template);
        $this->assertDatabaseHas('templates', [
            'id' => $template->id,
            'name' => 'Welcome Email',
            'engine' => 'blade',
        ]);
    }

    public function test_create_persists_template_with_metadata(): void
    {
        $template = $this->repository->create([
            'name' => 'Newsletter',
            'engine' => TemplateEngine::Twig->value,
            'subject_template' => 'News for {{ name }}',
            'body_content' => '<h1>Newsletter</h1>',
            'metadata' => ['category' => 'marketing'],
        ]);

        $this->assertInstanceOf(Template::class, $template);
        $this->assertEquals(['category' => 'marketing'], $template->metadata);
    }
}
