<?php

namespace Tests\Feature\Repositories\Eloquent;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Eloquent\ModelNotFoundException;

use App\Models\Campaign;
use App\Models\Template;
use App\Enums\CampaignStatus;
use App\Contracts\CampaignRepositoryInterface;
use App\Repositories\EloquentCampaignRepository;

class EloquentCampaignRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private CampaignRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = app(EloquentCampaignRepository::class);
    }

    public function test_find_returns_campaign_by_id(): void
    {
        $campaign = Campaign::factory()->create();

        $found = $this->repository->find($campaign->id);

        $this->assertNotNull($found);
        $this->assertEquals($campaign->id, $found->id);
        $this->assertEquals($campaign->name, $found->name);
    }

    public function test_find_returns_null_for_nonexistent_id(): void
    {
        $result = $this->repository->find(999);

        $this->assertNull($result);
    }

    public function test_find_or_fail_returns_campaign_by_id(): void
    {
        $campaign = Campaign::factory()->create();

        $found = $this->repository->findOrFail($campaign->id);

        $this->assertEquals($campaign->id, $found->id);
    }

    public function test_find_or_fail_throws_exception_for_nonexistent_id(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $this->repository->findOrFail(999);
    }

    public function test_all_returns_empty_collection_when_no_campaigns(): void
    {
        $result = $this->repository->all();

        $this->assertCount(0, $result);
    }

    public function test_all_returns_all_campaigns(): void
    {
        Campaign::factory()->count(3)->create();

        $result = $this->repository->all();

        $this->assertCount(3, $result);
    }

    public function test_create_persists_campaign(): void
    {
        $template = Template::factory()->create();
        $data = [
            'name' => 'Test Campaign',
            'subject' => 'Hello World',
            'template_id' => $template->id,
            'sender_channel' => 'smtp',
            'status' => CampaignStatus::Draft,
        ];

        $campaign = $this->repository->create($data);

        $this->assertDatabaseHas('campaigns', [
            'id' => $campaign->id,
            'name' => 'Test Campaign',
            'subject' => 'Hello World',
            'sender_channel' => 'smtp',
            'status' => CampaignStatus::Draft->value,
        ]);
        $this->assertInstanceOf(Campaign::class, $campaign);
    }

    public function test_create_sets_scheduled_at_when_provided(): void
    {
        $template = Template::factory()->create();
        $scheduledAt = now()->addDay();

        $campaign = $this->repository->create([
            'name' => 'Scheduled Campaign',
            'subject' => 'Scheduled Subject',
            'template_id' => $template->id,
            'sender_channel' => 'sendgrid',
            'status' => CampaignStatus::CollectingSubscribers,
            'scheduled_at' => $scheduledAt,
        ]);

        $this->assertDatabaseHas('campaigns', ['id' => $campaign->id]);
        $this->assertEquals(
            $scheduledAt->toDateTimeString(),
            $campaign->scheduled_at->toDateTimeString()
        );
    }

    public function test_find_by_status_returns_matching_campaigns(): void
    {
        Campaign::factory()->count(2)->create(['status' => CampaignStatus::Draft]);
        Campaign::factory()->create(['status' => CampaignStatus::Sending]);
        Campaign::factory()->create(['status' => CampaignStatus::Sent]);

        $drafts = $this->repository->findByStatus(CampaignStatus::Draft->value);
        $sending = $this->repository->findByStatus(CampaignStatus::Sending->value);
        $sent = $this->repository->findByStatus(CampaignStatus::Sent->value);

        $this->assertCount(2, $drafts);
        $this->assertCount(1, $sending);
        $this->assertCount(1, $sent);
    }

    public function test_find_by_status_returns_empty_collection_for_no_matches(): void
    {
        Campaign::factory()->create(['status' => CampaignStatus::Draft]);

        $result = $this->repository->findByStatus(CampaignStatus::Sent->value);

        $this->assertCount(0, $result);
    }

    public function test_mark_as_started_updates_status(): void
    {
        $campaign = Campaign::factory()->create(['status' => CampaignStatus::Draft]);

        $updated = $this->repository->markAsStarted($campaign->id);

        $this->assertEquals(CampaignStatus::Started, $updated->status);
        $this->assertDatabaseHas('campaigns', [
            'id' => $campaign->id,
            'status' => CampaignStatus::Started->value,
        ]);
    }

    public function test_mark_as_started_throws_for_nonexistent_campaign(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $this->repository->markAsStarted(999);
    }
}
