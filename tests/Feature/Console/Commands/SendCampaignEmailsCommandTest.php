<?php

namespace Tests\Feature\Console\Commands;

use Tests\TestCase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Foundation\Testing\RefreshDatabase;

use App\Models\Campaign;
use App\Models\Subscriber;
use App\Enums\CampaignStatus;
use App\Jobs\SendCampaignEmailJob;

class SendCampaignEmailsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_dispatches_jobs_in_batches_of_50(): void
    {
        Queue::fake();

        $campaign = Campaign::factory()->create(['status' => CampaignStatus::SubscribersCollected]);
        $subscribers = Subscriber::factory()->count(120)->create();

        $campaign->subscribers()->attach(
            $subscribers->pluck('id')->mapWithKeys(fn ($id) => [$id => ['status' => 'pending']])->all()
        );

        $this->artisan('campaigns:send-emails')
            ->assertSuccessful();

        Queue::assertPushed(SendCampaignEmailJob::class, 3);

        $dispatchedIds = collect();

        Queue::assertPushed(SendCampaignEmailJob::class, function (SendCampaignEmailJob $job) use ($campaign, &$dispatchedIds) {
            if ($job->campaign->id !== $campaign->id) {
                return false;
            }

            $dispatchedIds = $dispatchedIds->merge($job->subscriberIds);

            return count($job->subscriberIds) <= 50;
        });

        $this->assertCount(120, $dispatchedIds);
        $this->assertEquals(
            $subscribers->pluck('id')->sort()->values()->all(),
            $dispatchedIds->sort()->values()->all()
        );
    }

    public function test_transitions_campaign_to_sending(): void
    {
        Queue::fake();

        $campaign = Campaign::factory()->create(['status' => CampaignStatus::SubscribersCollected]);
        $subscriber = Subscriber::factory()->create();

        $campaign->subscribers()->attach($subscriber->id, ['status' => 'pending']);

        $this->artisan('campaigns:send-emails')
            ->assertSuccessful();

        $this->assertEquals(CampaignStatus::Sending, $campaign->refresh()->status);
    }

    public function test_skips_campaigns_not_in_subscribers_collected_status(): void
    {
        Queue::fake();

        Campaign::factory()->create(['status' => CampaignStatus::Draft]);
        Campaign::factory()->create(['status' => CampaignStatus::Started]);
        Campaign::factory()->create(['status' => CampaignStatus::Sending]);
        Campaign::factory()->create(['status' => CampaignStatus::Sent]);

        Subscriber::factory()->count(3)->create();

        $this->artisan('campaigns:send-emails')
            ->assertSuccessful();

        Queue::assertNotPushed(SendCampaignEmailJob::class);
    }

    public function test_only_processes_pending_pivot_subscribers(): void
    {
        Queue::fake();

        $campaign = Campaign::factory()->create(['status' => CampaignStatus::SubscribersCollected]);
        $pending = Subscriber::factory()->count(2)->create();
        $sent = Subscriber::factory()->create();
        $failed = Subscriber::factory()->create();

        $campaign->subscribers()->attach(
            $pending->pluck('id')->mapWithKeys(fn ($id) => [$id => ['status' => 'pending']])->all()
        );
        $campaign->subscribers()->attach($sent->id, ['status' => 'sent']);
        $campaign->subscribers()->attach($failed->id, ['status' => 'failed']);

        $this->artisan('campaigns:send-emails')
            ->assertSuccessful();

        Queue::assertPushed(SendCampaignEmailJob::class, 1);

        Queue::assertPushed(SendCampaignEmailJob::class, function (SendCampaignEmailJob $job) use ($pending) {
            return $job->subscriberIds == $pending->pluck('id')->all();
        });
    }

    public function test_does_nothing_when_no_eligible_campaigns_exist(): void
    {
        Queue::fake();

        $this->artisan('campaigns:send-emails')
            ->expectsOutputToContain('No campaigns ready to send.')
            ->assertSuccessful();

        Queue::assertNotPushed(SendCampaignEmailJob::class);
    }

    public function test_handles_multiple_campaigns(): void
    {
        Queue::fake();

        $campaign1 = Campaign::factory()->create(['status' => CampaignStatus::SubscribersCollected]);
        $campaign2 = Campaign::factory()->create(['status' => CampaignStatus::SubscribersCollected]);

        $subscribers1 = Subscriber::factory()->count(3)->create();
        $subscribers2 = Subscriber::factory()->count(5)->create();

        $campaign1->subscribers()->attach(
            $subscribers1->pluck('id')->mapWithKeys(fn ($id) => [$id => ['status' => 'pending']])->all()
        );
        $campaign2->subscribers()->attach(
            $subscribers2->pluck('id')->mapWithKeys(fn ($id) => [$id => ['status' => 'pending']])->all()
        );

        $this->artisan('campaigns:send-emails')
            ->assertSuccessful();

        $this->assertEquals(CampaignStatus::Sending, $campaign1->refresh()->status);
        $this->assertEquals(CampaignStatus::Sending, $campaign2->refresh()->status);

        Queue::assertPushed(SendCampaignEmailJob::class, 2);

        Queue::assertPushed(SendCampaignEmailJob::class, function (SendCampaignEmailJob $job) use ($campaign1, $subscribers1) {
            return $job->campaign->id === $campaign1->id
                && $job->subscriberIds == $subscribers1->pluck('id')->all();
        });

        Queue::assertPushed(SendCampaignEmailJob::class, function (SendCampaignEmailJob $job) use ($campaign2, $subscribers2) {
            return $job->campaign->id === $campaign2->id
                && $job->subscriberIds == $subscribers2->pluck('id')->all();
        });
    }
}
