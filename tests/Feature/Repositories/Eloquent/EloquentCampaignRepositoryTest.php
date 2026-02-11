<?php

namespace Tests\Feature\Repositories\Eloquent;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

use App\Models\Campaign;
use App\Models\Template;
use App\Models\Subscriber;
use App\Enums\CampaignStatus;
use App\Enums\CampaignSubscriberStatus;
use App\Repositories\EloquentCampaignRepository;
use App\Contracts\Repositories\CampaignRepositoryInterface;

class EloquentCampaignRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private CampaignRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = app(EloquentCampaignRepository::class);
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

    public function test_update_modifies_campaign_attributes(): void
    {
        $campaign = Campaign::factory()->create(['name' => 'Old Name']);

        $updated = $this->repository->update($campaign->id, ['name' => 'New Name']);

        $this->assertEquals('New Name', $updated->name);
        $this->assertDatabaseHas('campaigns', [
            'id' => $campaign->id,
            'name' => 'New Name',
        ]);
    }

    public function test_update_throws_for_nonexistent_campaign(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $this->repository->update(999, ['name' => 'New Name']);
    }

    public function test_update_status_changes_campaign_status(): void
    {
        $campaign = Campaign::factory()->create(['status' => CampaignStatus::Draft]);

        $this->repository->updateStatus($campaign->id, CampaignStatus::Sending);

        $this->assertDatabaseHas('campaigns', [
            'id' => $campaign->id,
            'status' => CampaignStatus::Sending->value,
        ]);
    }

    public function test_update_status_throws_for_nonexistent_campaign(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $this->repository->updateStatus(999, CampaignStatus::Sending);
    }

    public function test_chunk_pending_subscribers_iterates_pending_records(): void
    {
        $campaign = Campaign::factory()->create();
        $subscribers = Subscriber::factory()->count(5)->create();

        foreach ($subscribers as $subscriber) {
            $campaign->subscribers()->attach($subscriber->id, [
                'status' => CampaignSubscriberStatus::Pending->value,
            ]);
        }

        $collectedIds = [];
        $this->repository->chunkPendingSubscribers($campaign->id, 2, function (array $subscriberIds) use (&$collectedIds) {
            $collectedIds = array_merge($collectedIds, $subscriberIds);
        });

        $this->assertEqualsCanonicalizing(
            $subscribers->pluck('id')->all(),
            $collectedIds
        );
    }

    public function test_chunk_pending_subscribers_skips_non_pending_records(): void
    {
        $campaign = Campaign::factory()->create();
        $pending1 = Subscriber::factory()->create();
        $pending2 = Subscriber::factory()->create();
        $sent = Subscriber::factory()->create();

        $campaign->subscribers()->attach($pending1->id, ['status' => CampaignSubscriberStatus::Pending->value]);
        $campaign->subscribers()->attach($pending2->id, ['status' => CampaignSubscriberStatus::Pending->value]);
        $campaign->subscribers()->attach($sent->id, ['status' => CampaignSubscriberStatus::Sent->value]);

        $collectedIds = [];
        $this->repository->chunkPendingSubscribers($campaign->id, 10, function (array $subscriberIds) use (&$collectedIds) {
            $collectedIds = array_merge($collectedIds, $subscriberIds);
        });

        $this->assertEqualsCanonicalizing([$pending1->id, $pending2->id], $collectedIds);
    }

    public function test_paginate_returns_paginated_campaigns(): void
    {
        Campaign::factory()->count(20)->create();

        $result = $this->repository->paginate(10);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertCount(10, $result->items());
        $this->assertEquals(20, $result->total());
    }

    public function test_paginate_includes_subscriber_count_aggregates(): void
    {
        $campaign = Campaign::factory()->create();
        $subscribers = Subscriber::factory()->count(3)->create();

        $campaign->subscribers()->attach($subscribers[0]->id, ['status' => CampaignSubscriberStatus::Sent->value]);
        $campaign->subscribers()->attach($subscribers[1]->id, ['status' => CampaignSubscriberStatus::Failed->value]);
        $campaign->subscribers()->attach($subscribers[2]->id, ['status' => CampaignSubscriberStatus::Pending->value]);

        $result = $this->repository->paginate(10);
        $item = $result->items()[0];

        $this->assertEquals(3, $item->total_recipients);
        $this->assertEquals(1, $item->total_sent);
        $this->assertEquals(1, $item->total_failed);
    }
}
