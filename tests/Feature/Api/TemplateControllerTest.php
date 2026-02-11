<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

use App\Models\Template;
use App\Enums\TemplateEngine;

class TemplateControllerTest extends TestCase
{
    use RefreshDatabase;

    private function validTemplatePayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Welcome Email',
            'engine' => TemplateEngine::Blade->value,
            'subject_template' => 'Hello {{ $name }}',
            'body_content' => '<h1>Welcome</h1>',
        ], $overrides);
    }

    public function test_index_returns_paginated_templates(): void
    {
        Template::factory()->count(3)->create();

        $response = $this->getJson('/api/templates');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure(['data', 'links', 'meta']);
    }

    public function test_index_respects_per_page_parameter(): void
    {
        Template::factory()->count(5)->create();

        $response = $this->getJson('/api/templates?per_page=2');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.per_page', 2)
            ->assertJsonPath('meta.total', 5);
    }

    public function test_store_creates_template(): void
    {
        $payload = $this->validTemplatePayload();

        $response = $this->postJson('/api/templates', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Welcome Email')
            ->assertJsonPath('message', 'Template created successfully.');

        $this->assertDatabaseHas('templates', [
            'name' => 'Welcome Email',
            'engine' => 'blade',
        ]);
    }

    public function test_store_creates_template_with_metadata(): void
    {
        $payload = $this->validTemplatePayload(['metadata' => ['category' => 'marketing']]);

        $response = $this->postJson('/api/templates', $payload);

        $response->assertStatus(201);
        $this->assertDatabaseHas('templates', ['name' => 'Welcome Email']);
    }

    public function test_store_returns_422_when_name_is_missing(): void
    {
        $payload = $this->validTemplatePayload();
        unset($payload['name']);

        $this->postJson('/api/templates', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_store_returns_422_when_engine_is_invalid(): void
    {
        $payload = $this->validTemplatePayload(['engine' => 'invalid']);

        $this->postJson('/api/templates', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['engine']);
    }

    public function test_store_returns_422_when_subject_template_is_missing(): void
    {
        $payload = $this->validTemplatePayload();
        unset($payload['subject_template']);

        $this->postJson('/api/templates', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['subject_template']);
    }

    public function test_store_returns_422_when_body_content_is_missing(): void
    {
        $payload = $this->validTemplatePayload();
        unset($payload['body_content']);

        $this->postJson('/api/templates', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['body_content']);
    }
}
