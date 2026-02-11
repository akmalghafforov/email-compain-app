<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

use App\Models\Campaign;
use App\Models\Template;
use App\Enums\CampaignStatus;

class CampaignControllerTest extends TestCase
{
    use RefreshDatabase;

    private function validCampaignPayload(array $overrides = []): array
    {
        $template = Template::factory()->create();

        return array_merge([
            'name' => 'Test Campaign',
            'subject' => 'Test Subject',
            'template_id' => $template->id,
            'sender_channel' => 'smtp',
        ], $overrides);
    }

    public function test_index_returns_paginated_campaigns(): void
    {
        Campaign::factory()->count(3)->create();

        $response = $this->getJson('/api/campaigns');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure(['data', 'links', 'meta']);
    }

    public function test_index_respects_per_page_parameter(): void
    {
        Campaign::factory()->count(5)->create();

        $response = $this->getJson('/api/campaigns?per_page=2');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.per_page', 2)
            ->assertJsonPath('meta.total', 5);
    }

    public function test_store_creates_campaign(): void
    {
        $payload = $this->validCampaignPayload();

        $response = $this->postJson('/api/campaigns', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Test Campaign')
            ->assertJsonPath('message', 'Campaign created successfully.');

        $this->assertDatabaseHas('campaigns', [
            'name' => 'Test Campaign',
            'subject' => 'Test Subject',
            'sender_channel' => 'smtp',
        ]);
    }

    public function test_store_creates_campaign_with_scheduled_at(): void
    {
        $scheduledAt = now()->addDay()->toDateTimeString();
        $payload = $this->validCampaignPayload(['scheduled_at' => $scheduledAt]);

        $response = $this->postJson('/api/campaigns', $payload);

        $response->assertStatus(201);
        $this->assertDatabaseHas('campaigns', [
            'name' => 'Test Campaign',
            'scheduled_at' => $scheduledAt,
        ]);
    }

    public function test_store_returns_422_when_name_is_missing(): void
    {
        $payload = $this->validCampaignPayload();
        unset($payload['name']);

        $this->postJson('/api/campaigns', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_store_returns_422_when_subject_is_missing(): void
    {
        $payload = $this->validCampaignPayload();
        unset($payload['subject']);

        $this->postJson('/api/campaigns', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['subject']);
    }

    public function test_store_returns_422_when_template_id_is_invalid(): void
    {
        $payload = $this->validCampaignPayload(['template_id' => 999]);

        $this->postJson('/api/campaigns', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['template_id']);
    }

    public function test_store_returns_422_when_sender_channel_is_invalid(): void
    {
        $payload = $this->validCampaignPayload(['sender_channel' => 'invalid']);

        $this->postJson('/api/campaigns', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['sender_channel']);
    }

    public function test_show_returns_campaign(): void
    {
        $campaign = Campaign::factory()->create();

        $response = $this->getJson("/api/campaigns/{$campaign->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $campaign->id)
            ->assertJsonPath('data.name', $campaign->name);
    }

    public function test_show_returns_404_for_nonexistent_campaign(): void
    {
        $this->getJson('/api/campaigns/999')
            ->assertStatus(404);
    }

    public function test_update_modifies_campaign(): void
    {
        $campaign = Campaign::factory()->create(['name' => 'Old Name']);

        $response = $this->putJson("/api/campaigns/{$campaign->id}", ['name' => 'New Name']);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'New Name')
            ->assertJsonPath('message', 'Campaign updated successfully.');
    }

    public function test_update_returns_404_for_nonexistent_campaign(): void
    {
        $this->putJson('/api/campaigns/999', ['name' => 'X'])
            ->assertStatus(404);
    }

    public function test_dispatch_marks_campaign_as_started(): void
    {
        $campaign = Campaign::factory()->create(['status' => CampaignStatus::Draft]);

        $response = $this->postJson("/api/campaigns/{$campaign->id}/dispatch");

        $response->assertStatus(200)
            ->assertJsonPath('data.status', CampaignStatus::Started->value)
            ->assertJsonPath('message', 'Campaign dispatched successfully.');

        $this->assertDatabaseHas('campaigns', [
            'id' => $campaign->id,
            'status' => CampaignStatus::Started->value,
        ]);
    }
}
