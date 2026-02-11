<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

use App\Models\Subscriber;

class SubscriberControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_returns_paginated_subscribers(): void
    {
        Subscriber::factory()->count(3)->create();

        $response = $this->getJson('/api/subscribers');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure(['data', 'links', 'meta']);
    }

    public function test_index_respects_per_page_parameter(): void
    {
        Subscriber::factory()->count(5)->create();

        $response = $this->getJson('/api/subscribers?per_page=2');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.per_page', 2)
            ->assertJsonPath('meta.total', 5);
    }

    public function test_index_returns_empty_data_when_no_subscribers(): void
    {
        $response = $this->getJson('/api/subscribers');

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data')
            ->assertJsonPath('meta.total', 0);
    }
}
