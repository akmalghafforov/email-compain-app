<?php

namespace Tests\Feature\Console\Commands;

use Tests\TestCase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Foundation\Testing\RefreshDatabase;

use App\Models\Campaign;
use App\Models\Subscriber;
use App\Enums\CampaignStatus;
use App\Contracts\Repositories\SubscriberRepositoryInterface;

class CollectCampaignSubscribersCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_collects_active_subscribers_for_started_campaigns(): void
    {
        $campaign = Campaign::factory()->create(['status' => CampaignStatus::Started]);
        $activeSubscribers = Subscriber::factory()->count(3)->create();
        Subscriber::factory()->unsubscribed()->count(2)->create();
        Subscriber::factory()->bounced()->create();

        $this->artisan('campaigns:collect-subscribers')
            ->assertSuccessful();

        $campaign->refresh();

        $this->assertEquals(CampaignStatus::SubscribersCollected, $campaign->status);
        $this->assertCount(3, $campaign->subscribers);

        foreach ($activeSubscribers as $subscriber) {
            $this->assertDatabaseHas('campaign_subscriber', [
                'campaign_id' => $campaign->id,
                'subscriber_id' => $subscriber->id,
                'status' => 'pending',
            ]);
        }
    }

    public function test_skips_campaigns_not_in_started_status(): void
    {
        $draftCampaign = Campaign::factory()->create(['status' => CampaignStatus::Draft]);
        $sendingCampaign = Campaign::factory()->create(['status' => CampaignStatus::Sending]);
        $sentCampaign = Campaign::factory()->create(['status' => CampaignStatus::Sent]);
        Subscriber::factory()->count(2)->create();

        $this->artisan('campaigns:collect-subscribers')
            ->assertSuccessful();

        $this->assertCount(0, $draftCampaign->refresh()->subscribers);
        $this->assertCount(0, $sendingCampaign->refresh()->subscribers);
        $this->assertCount(0, $sentCampaign->refresh()->subscribers);
    }

    public function test_transitions_campaign_to_subscribers_collected(): void
    {
        $campaign = Campaign::factory()->create(['status' => CampaignStatus::Started]);
        Subscriber::factory()->create();

        $this->artisan('campaigns:collect-subscribers')
            ->assertSuccessful();

        $this->assertEquals(CampaignStatus::SubscribersCollected, $campaign->refresh()->status);
    }

    public function test_handles_multiple_started_campaigns(): void
    {
        $campaigns = Campaign::factory()->count(3)->create(['status' => CampaignStatus::Started]);
        Subscriber::factory()->count(2)->create();

        $this->artisan('campaigns:collect-subscribers')
            ->assertSuccessful();

        foreach ($campaigns as $campaign) {
            $campaign->refresh();
            $this->assertEquals(CampaignStatus::SubscribersCollected, $campaign->status);
            $this->assertCount(2, $campaign->subscribers);
        }
    }

    public function test_does_not_attach_unsubscribed_subscribers(): void
    {
        $campaign = Campaign::factory()->create(['status' => CampaignStatus::Started]);
        Subscriber::factory()->create();
        $unsubscribed = Subscriber::factory()->unsubscribed()->create();

        $this->artisan('campaigns:collect-subscribers')
            ->assertSuccessful();

        $this->assertDatabaseMissing('campaign_subscriber', [
            'campaign_id' => $campaign->id,
            'subscriber_id' => $unsubscribed->id,
        ]);
    }

    public function test_does_not_attach_bounced_subscribers(): void
    {
        $campaign = Campaign::factory()->create(['status' => CampaignStatus::Started]);
        Subscriber::factory()->create();
        $bounced = Subscriber::factory()->bounced()->create();

        $this->artisan('campaigns:collect-subscribers')
            ->assertSuccessful();

        $this->assertDatabaseMissing('campaign_subscriber', [
            'campaign_id' => $campaign->id,
            'subscriber_id' => $bounced->id,
        ]);
    }

    public function test_marks_campaign_as_failed_when_no_active_subscribers_exist(): void
    {
        $campaign = Campaign::factory()->create(['status' => CampaignStatus::Started]);

        $this->artisan('campaigns:collect-subscribers')
            ->assertSuccessful();

        $this->assertEquals(CampaignStatus::Failed, $campaign->refresh()->status);
    }

    public function test_outputs_summary_of_collected_subscribers(): void
    {
        Campaign::factory()->create(['status' => CampaignStatus::Started]);
        Subscriber::factory()->count(5)->create();

        $this->artisan('campaigns:collect-subscribers')
            ->expectsOutputToContain('5 subscribers collected')
            ->assertSuccessful();
    }

    public function test_does_nothing_when_no_started_campaigns_exist(): void
    {
        Campaign::factory()->create(['status' => CampaignStatus::Draft]);
        Subscriber::factory()->count(3)->create();

        $this->artisan('campaigns:collect-subscribers')
            ->assertSuccessful();
    }

    public function test_does_not_duplicate_subscribers_already_attached(): void
    {
        $campaign = Campaign::factory()->create(['status' => CampaignStatus::Started]);
        $subscriber = Subscriber::factory()->create();

        $campaign->subscribers()->attach($subscriber->id, ['status' => 'pending']);

        $this->artisan('campaigns:collect-subscribers')
            ->assertSuccessful();

        $this->assertEquals(1, $campaign->subscribers()->count());
    }

    public function test_continues_processing_remaining_campaigns_when_one_fails(): void
    {
        $campaign1 = Campaign::factory()->create(['status' => CampaignStatus::Started]);
        $campaign2 = Campaign::factory()->create(['status' => CampaignStatus::Started]);
        Subscriber::factory()->count(2)->create();

        $callCount = 0;

        $this->mock(SubscriberRepositoryInterface::class, function ($mock) use (&$callCount) {
            $mock->shouldReceive('segmentByQuery')
                ->andReturnUsing(function () use (&$callCount) {
                    $callCount++;
                    // Fail all 3 retries for campaign1, then succeed for campaign2
                    if ($callCount <= 3) {
                        throw new \RuntimeException('Database connection lost');
                    }

                    return Subscriber::query()
                        ->where('status', 'active')
                        ->whereNull('unsubscribed_at');
                });
        });

        $this->artisan('campaigns:collect-subscribers')
            ->assertSuccessful();

        $this->assertEquals(CampaignStatus::Failed, $campaign1->refresh()->status);
        $this->assertEquals(CampaignStatus::SubscribersCollected, $campaign2->refresh()->status);
        $this->assertCount(2, $campaign2->subscribers);
    }

    public function test_marks_campaign_as_failed_after_exhausting_retries(): void
    {
        $campaign = Campaign::factory()->create(['status' => CampaignStatus::Started]);
        Subscriber::factory()->create();

        $this->mock(SubscriberRepositoryInterface::class, function ($mock) {
            $mock->shouldReceive('segmentByQuery')
                ->andThrow(new \RuntimeException('Unexpected error'));
        });

        $this->artisan('campaigns:collect-subscribers')
            ->assertSuccessful();

        $this->assertEquals(CampaignStatus::Failed, $campaign->refresh()->status);
    }

    public function test_retries_on_failure_and_succeeds(): void
    {
        $campaign = Campaign::factory()->create(['status' => CampaignStatus::Started]);
        Subscriber::factory()->count(2)->create();

        $callCount = 0;

        $this->mock(SubscriberRepositoryInterface::class, function ($mock) use (&$callCount) {
            $mock->shouldReceive('segmentByQuery')
                ->andReturnUsing(function () use (&$callCount) {
                    $callCount++;
                    if ($callCount === 1) {
                        throw new \RuntimeException('Temporary failure');
                    }

                    return Subscriber::query()
                        ->where('status', 'active')
                        ->whereNull('unsubscribed_at');
                });
        });

        $this->artisan('campaigns:collect-subscribers')
            ->assertSuccessful();

        $this->assertEquals(CampaignStatus::SubscribersCollected, $campaign->refresh()->status);
        $this->assertCount(2, $campaign->subscribers);
    }

    public function test_retry_skips_already_collected_subscribers(): void
    {
        $campaign = Campaign::factory()->create(['status' => CampaignStatus::Started]);
        $alreadyAttached = Subscriber::factory()->create();
        $newSubscriber = Subscriber::factory()->create();

        // Simulate partially collected state from a previous interrupted attempt
        $campaign->subscribers()->attach($alreadyAttached->id, ['status' => 'pending']);

        $this->artisan('campaigns:collect-subscribers')
            ->assertSuccessful();

        $campaign->refresh();

        $this->assertEquals(CampaignStatus::SubscribersCollected, $campaign->status);
        $this->assertCount(2, $campaign->subscribers);

        $this->assertDatabaseHas('campaign_subscriber', [
            'campaign_id' => $campaign->id,
            'subscriber_id' => $newSubscriber->id,
            'status' => 'pending',
        ]);
    }

    public function test_skips_campaign_when_lock_is_already_held(): void
    {
        $lockedCampaign = Campaign::factory()->create(['status' => CampaignStatus::Started]);
        $freeCampaign = Campaign::factory()->create(['status' => CampaignStatus::Started]);
        Subscriber::factory()->count(2)->create();

        $lock = Cache::lock("campaign:{$lockedCampaign->id}:collecting-subscribers", 10 * 60);
        $lock->get();

        try {
            $this->artisan('campaigns:collect-subscribers')
                ->expectsOutputToContain("Campaign #{$lockedCampaign->id} is already being processed, skipping.")
                ->assertSuccessful();

            $this->assertEquals(CampaignStatus::Started, $lockedCampaign->refresh()->status);
            $this->assertCount(0, $lockedCampaign->subscribers);

            $this->assertEquals(CampaignStatus::SubscribersCollected, $freeCampaign->refresh()->status);
            $this->assertCount(2, $freeCampaign->subscribers);
        } finally {
            $lock->release();
        }
    }

    public function test_respects_custom_tries_option(): void
    {
        $campaign = Campaign::factory()->create(['status' => CampaignStatus::Started]);
        Subscriber::factory()->create();

        $this->mock(SubscriberRepositoryInterface::class, function ($mock) {
            $mock->shouldReceive('segmentByQuery')
                ->andThrow(new \RuntimeException('Persistent failure'));
        });

        $this->artisan('campaigns:collect-subscribers', ['--tries' => 1])
            ->expectsOutputToContain('attempt 1/1 failed')
            ->expectsOutputToContain('failed after 1 attempts')
            ->assertSuccessful();

        $this->assertEquals(CampaignStatus::Failed, $campaign->refresh()->status);
    }
}
