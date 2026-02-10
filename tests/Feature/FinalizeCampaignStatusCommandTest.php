<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

use App\Models\Campaign;
use App\DTOs\CampaignStats;
use App\Enums\CampaignStatus;
use App\Contracts\DeliveryTrackerInterface;

class FinalizeCampaignStatusCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_sets_sent_status_when_all_subscribers_sent(): void
    {
        $campaign = Campaign::factory()->create(['status' => CampaignStatus::Sending]);

        $this->mockDeliveryTracker(totalRecipients: 10, totalSent: 10, totalFailed: 0);

        $this->artisan('campaigns:finalize-status')
            ->assertSuccessful();

        $this->assertEquals(CampaignStatus::Sent, $campaign->refresh()->status);
    }

    public function test_sets_failed_status_when_all_subscribers_failed(): void
    {
        $campaign = Campaign::factory()->create(['status' => CampaignStatus::Sending]);

        $this->mockDeliveryTracker(totalRecipients: 10, totalSent: 0, totalFailed: 10);

        $this->artisan('campaigns:finalize-status')
            ->assertSuccessful();

        $this->assertEquals(CampaignStatus::Failed, $campaign->refresh()->status);
    }

    public function test_sets_partially_sent_status_when_mix_of_sent_and_failed(): void
    {
        $campaign = Campaign::factory()->create(['status' => CampaignStatus::Sending]);

        $this->mockDeliveryTracker(totalRecipients: 10, totalSent: 7, totalFailed: 3);

        $this->artisan('campaigns:finalize-status')
            ->assertSuccessful();

        $this->assertEquals(CampaignStatus::PartiallySent, $campaign->refresh()->status);
    }

    public function test_skips_campaign_with_pending_subscribers(): void
    {
        $campaign = Campaign::factory()->create(['status' => CampaignStatus::Sending]);

        $this->mockDeliveryTracker(totalRecipients: 10, totalSent: 5, totalFailed: 2);

        $this->artisan('campaigns:finalize-status')
            ->expectsOutputToContain('pending')
            ->assertSuccessful();

        $this->assertEquals(CampaignStatus::Sending, $campaign->refresh()->status);
    }

    public function test_does_nothing_when_no_sending_campaigns(): void
    {
        Campaign::factory()->create(['status' => CampaignStatus::Draft]);
        Campaign::factory()->create(['status' => CampaignStatus::Sent]);

        $this->artisan('campaigns:finalize-status')
            ->expectsOutputToContain('No campaigns in sending status.')
            ->assertSuccessful();
    }

    public function test_handles_multiple_campaigns(): void
    {
        $campaign1 = Campaign::factory()->create(['status' => CampaignStatus::Sending]);
        $campaign2 = Campaign::factory()->create(['status' => CampaignStatus::Sending]);

        $stats1 = new CampaignStats(
            totalRecipients: 5,
            totalSent: 5,
            totalOpened: 0,
            totalClicked: 0,
            totalBounced: 0,
            totalFailed: 0,
            openRate: 0.0,
            clickRate: 0.0,
            bounceRate: 0.0,
        );

        $stats2 = new CampaignStats(
            totalRecipients: 3,
            totalSent: 0,
            totalOpened: 0,
            totalClicked: 0,
            totalBounced: 0,
            totalFailed: 3,
            openRate: 0.0,
            clickRate: 0.0,
            bounceRate: 0.0,
        );

        $mock = $this->mock(DeliveryTrackerInterface::class);
        $mock->shouldReceive('getStats')
            ->with(\Mockery::on(fn (Campaign $c) => $c->id === $campaign1->id))
            ->andReturn($stats1);
        $mock->shouldReceive('getStats')
            ->with(\Mockery::on(fn (Campaign $c) => $c->id === $campaign2->id))
            ->andReturn($stats2);

        $this->artisan('campaigns:finalize-status')
            ->assertSuccessful();

        $this->assertEquals(CampaignStatus::Sent, $campaign1->refresh()->status);
        $this->assertEquals(CampaignStatus::Failed, $campaign2->refresh()->status);
    }

    public function test_does_not_affect_non_sending_campaigns(): void
    {
        $draft = Campaign::factory()->create(['status' => CampaignStatus::Draft]);
        $sent = Campaign::factory()->create(['status' => CampaignStatus::Sent]);
        $sending = Campaign::factory()->create(['status' => CampaignStatus::Sending]);

        $this->mockDeliveryTracker(totalRecipients: 5, totalSent: 5, totalFailed: 0);

        $this->artisan('campaigns:finalize-status')
            ->assertSuccessful();

        $this->assertEquals(CampaignStatus::Draft, $draft->refresh()->status);
        $this->assertEquals(CampaignStatus::Sent, $sent->refresh()->status);
        $this->assertEquals(CampaignStatus::Sent, $sending->refresh()->status);
    }

    private function mockDeliveryTracker(int $totalRecipients, int $totalSent, int $totalFailed): void
    {
        $stats = new CampaignStats(
            totalRecipients: $totalRecipients,
            totalSent: $totalSent,
            totalOpened: 0,
            totalClicked: 0,
            totalBounced: 0,
            totalFailed: $totalFailed,
            openRate: 0.0,
            clickRate: 0.0,
            bounceRate: 0.0,
        );

        $this->mock(DeliveryTrackerInterface::class)
            ->shouldReceive('getStats')
            ->andReturn($stats);
    }
}
