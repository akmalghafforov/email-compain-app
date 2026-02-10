<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

use App\DTOs\SendResult;
use App\DTOs\CampaignStats;
use App\Models\Campaign;
use App\Models\Template;
use App\Models\Subscriber;
use App\Models\DeliveryLog;
use App\Enums\CampaignStatus;
use App\Enums\CampaignSubscriberStatus;
use App\Enums\DeliveryLogEvent;
use App\Services\Delivery\DeliveryTracker;

class DeliveryTrackerTest extends TestCase
{
    use RefreshDatabase;

    private DeliveryTracker $tracker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tracker = new DeliveryTracker();
    }

    private function createCampaignWithSubscriber(): array
    {
        $template = Template::factory()->create();

        $campaign = Campaign::factory()->create([
            'status' => CampaignStatus::Sending,
            'template_id' => $template->id,
            'sender_channel' => 'smtp',
        ]);

        $subscriber = Subscriber::factory()->create();

        $campaign->subscribers()->attach($subscriber->id, ['status' => CampaignSubscriberStatus::Pending]);

        return [$campaign, $subscriber];
    }

    public function test_record_sent_updates_pivot_status_and_sent_at(): void
    {
        [$campaign, $subscriber] = $this->createCampaignWithSubscriber();
        $result = new SendResult('msg-001', 'sent');

        $this->tracker->recordSent($campaign, $subscriber, $result);

        $pivot = $campaign->subscribers()->where('subscriber_id', $subscriber->id)->first()->pivot;
        $this->assertEquals(CampaignSubscriberStatus::Sent, $pivot->status);
        $this->assertNotNull($pivot->sent_at);
    }

    public function test_record_sent_creates_delivery_log(): void
    {
        [$campaign, $subscriber] = $this->createCampaignWithSubscriber();
        $result = new SendResult('msg-002', 'sent');

        $this->tracker->recordSent($campaign, $subscriber, $result);

        $log = DeliveryLog::where('campaign_id', $campaign->id)
            ->where('subscriber_id', $subscriber->id)
            ->first();

        $this->assertNotNull($log);
        $this->assertEquals(DeliveryLogEvent::Sent, $log->event);
        $this->assertEquals('smtp', $log->channel);
        $this->assertEquals(['message_id' => 'msg-002'], $log->payload);
    }

    public function test_record_failed_updates_pivot_status_and_reason(): void
    {
        [$campaign, $subscriber] = $this->createCampaignWithSubscriber();

        $this->tracker->recordFailed($campaign, $subscriber, 'SMTP connection refused');

        $pivot = $campaign->subscribers()->where('subscriber_id', $subscriber->id)->first()->pivot;
        $this->assertEquals(CampaignSubscriberStatus::Failed, $pivot->status);
        $this->assertEquals('SMTP connection refused', $pivot->failed_reason);
    }

    public function test_record_failed_creates_delivery_log(): void
    {
        [$campaign, $subscriber] = $this->createCampaignWithSubscriber();

        $this->tracker->recordFailed($campaign, $subscriber, 'Connection timeout');

        $log = DeliveryLog::where('campaign_id', $campaign->id)
            ->where('subscriber_id', $subscriber->id)
            ->first();

        $this->assertNotNull($log);
        $this->assertEquals(DeliveryLogEvent::Failed, $log->event);
        $this->assertEquals('smtp', $log->channel);
        $this->assertEquals(['error' => 'Connection timeout'], $log->payload);
    }

    public function test_get_stats_returns_correct_aggregated_counts(): void
    {
        [$campaign, $subscriber1] = $this->createCampaignWithSubscriber();

        $subscriber2 = Subscriber::factory()->create();
        $campaign->subscribers()->attach($subscriber2->id, ['status' => 'pending']);

        $subscriber3 = Subscriber::factory()->create();
        $campaign->subscribers()->attach($subscriber3->id, ['status' => 'pending']);

        // Record mixed events
        $this->tracker->recordSent($campaign, $subscriber1, new SendResult('msg-1', 'sent'));
        $this->tracker->recordSent($campaign, $subscriber2, new SendResult('msg-2', 'sent'));
        $this->tracker->recordFailed($campaign, $subscriber3, 'Error');

        $stats = $this->tracker->getStats($campaign->id);

        $this->assertInstanceOf(CampaignStats::class, $stats);
        $this->assertEquals(3, $stats->totalRecipients);
        $this->assertEquals(2, $stats->totalSent);
        $this->assertEquals(1, $stats->totalFailed);
        $this->assertEquals(0, $stats->totalOpened);
        $this->assertEquals(0, $stats->totalClicked);

    }

    public function test_get_stats_returns_zero_rates_when_no_emails_sent(): void
    {
        [$campaign, $subscriber] = $this->createCampaignWithSubscriber();

        $this->tracker->recordFailed($campaign, $subscriber, 'Error');

        $stats = $this->tracker->getStats($campaign->id);

        $this->assertEquals(0.0, $stats->openRate);
        $this->assertEquals(0.0, $stats->clickRate);

    }
}
