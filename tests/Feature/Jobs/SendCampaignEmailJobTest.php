<?php

namespace Tests\Feature\Jobs;

use Mockery;
use Tests\TestCase;
use Mockery\MockInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;

use App\Models\Campaign;
use App\Models\Template;
use App\DTOs\SendResult;
use App\Models\Subscriber;
use App\Models\DeliveryLog;
use App\Enums\TemplateEngine;
use App\Enums\CampaignStatus;
use App\Enums\DeliveryLogEvent;
use App\Jobs\SendCampaignEmailJob;
use App\Exceptions\SendFailedException;
use App\Enums\CampaignSubscriberStatus;
use App\Contracts\EmailSenderInterface;
use App\Services\Template\TemplateRenderer;

class SendCampaignEmailJobTest extends TestCase
{
    use RefreshDatabase;

    private function createCampaignWithSubscribers(int $subscriberCount = 3): array
    {
        $template = Template::factory()->create([
            'engine' => TemplateEngine::Blade,
            'body_content' => 'Hello {{ $name }}',
        ]);

        $campaign = Campaign::factory()->create([
            'status' => CampaignStatus::Sending,
            'template_id' => $template->id,
            'sender_channel' => 'smtp',
        ]);

        $subscribers = Subscriber::factory()->count($subscriberCount)->create();

        $campaign->subscribers()->attach(
            $subscribers->pluck('id')->mapWithKeys(fn ($id) => [$id => ['status' => CampaignSubscriberStatus::Pending]])->all()
        );

        return [$campaign, $subscribers, $template];
    }

    public function test_sends_email_to_each_subscriber_and_updates_pivot_to_sent(): void
    {
        [$campaign, $subscribers] = $this->createCampaignWithSubscribers(3);

        $this->mock(TemplateRenderer::class, function (MockInterface $mock) {
            $mock->shouldReceive('render')
                ->andReturn('<p>Rendered content</p>');
        });

        $this->mock(EmailSenderInterface::class, function (MockInterface $mock) {
            $mock->shouldReceive('send')
                ->times(3)
                ->andReturn(new SendResult('msg-123', 'sent'));
        });

        $job = new SendCampaignEmailJob($campaign, $subscribers->pluck('id')->all());
        app()->call([$job, 'handle']);

        foreach ($subscribers as $subscriber) {
            $pivot = $campaign->subscribers()->where('subscriber_id', $subscriber->id)->first()->pivot;
            $this->assertEquals(CampaignSubscriberStatus::Sent, $pivot->status);
            $this->assertNotNull($pivot->sent_at);
        }
    }

    public function test_creates_delivery_log_for_each_successful_send(): void
    {
        [$campaign, $subscribers] = $this->createCampaignWithSubscribers(2);

        $this->mock(TemplateRenderer::class, function (MockInterface $mock) {
            $mock->shouldReceive('render')->andReturn('<p>Content</p>');
        });

        $this->mock(EmailSenderInterface::class, function (MockInterface $mock) {
            $mock->shouldReceive('send')
                ->andReturn(new SendResult('msg-456', 'sent'));
        });

        $job = new SendCampaignEmailJob($campaign, $subscribers->pluck('id')->all());
        app()->call([$job, 'handle']);

        $this->assertCount(2, DeliveryLog::where('campaign_id', $campaign->id)->get());

        $log = DeliveryLog::where('campaign_id', $campaign->id)->first();
        $this->assertEquals(DeliveryLogEvent::Sent, $log->event);
        $this->assertEquals('smtp', $log->channel);
        $this->assertArrayHasKey('message_id', $log->payload);
    }

    public function test_marks_subscriber_as_failed_when_send_throws(): void
    {
        [$campaign, $subscribers] = $this->createCampaignWithSubscribers(1);

        $this->mock(TemplateRenderer::class, function (MockInterface $mock) {
            $mock->shouldReceive('render')->andReturn('<p>Content</p>');
        });

        $this->mock(EmailSenderInterface::class, function (MockInterface $mock) {
            $mock->shouldReceive('send')
                ->andThrow(new SendFailedException('SMTP connection refused'));
        });

        $job = new SendCampaignEmailJob($campaign, $subscribers->pluck('id')->all());
        app()->call([$job, 'handle']);

        $pivot = $campaign->subscribers()->where('subscriber_id', $subscribers->first()->id)->first()->pivot;
        $this->assertEquals(CampaignSubscriberStatus::Failed, $pivot->status);
        $this->assertEquals('SMTP connection refused', $pivot->failed_reason);

        $log = DeliveryLog::where('campaign_id', $campaign->id)->first();
        $this->assertEquals(DeliveryLogEvent::Failed, $log->event);
    }

    public function test_handles_mixed_success_and_failure(): void
    {
        [$campaign, $subscribers] = $this->createCampaignWithSubscribers(3);

        $this->mock(TemplateRenderer::class, function (MockInterface $mock) {
            $mock->shouldReceive('render')->andReturn('<p>Content</p>');
        });

        $callCount = 0;
        $this->mock(EmailSenderInterface::class, function (MockInterface $mock) use (&$callCount) {
            $mock->shouldReceive('send')
                ->times(3)
                ->andReturnUsing(function () use (&$callCount) {
                    $callCount++;
                    if ($callCount === 2) {
                        throw new SendFailedException('Temporary failure');
                    }
                    return new SendResult("msg-{$callCount}", 'sent');
                });
        });

        $job = new SendCampaignEmailJob($campaign, $subscribers->pluck('id')->all());
        app()->call([$job, 'handle']);

        $sentCount = $campaign->subscribers()->wherePivot('status', CampaignSubscriberStatus::Sent->value)->count();
        $failedCount = $campaign->subscribers()->wherePivot('status', CampaignSubscriberStatus::Failed->value)->count();

        $this->assertEquals(2, $sentCount);
        $this->assertEquals(1, $failedCount);
        $this->assertCount(3, DeliveryLog::where('campaign_id', $campaign->id)->get());
    }

    public function test_failed_method_marks_remaining_pending_subscribers_as_failed(): void
    {
        [$campaign, $subscribers] = $this->createCampaignWithSubscribers(3);

        // Simulate one already sent before job failure
        $campaign->subscribers()->updateExistingPivot($subscribers->first()->id, [
            'status' => CampaignSubscriberStatus::Sent->value,
            'sent_at' => now(),
        ]);

        $job = new SendCampaignEmailJob($campaign, $subscribers->pluck('id')->all());
        $job->failed(new \RuntimeException('Queue timeout'));

        // The already-sent subscriber should not be changed
        $sentPivot = $campaign->subscribers()->where('subscriber_id', $subscribers->first()->id)->first()->pivot;
        $this->assertEquals(CampaignSubscriberStatus::Sent, $sentPivot->status);

        // The remaining pending subscribers should be marked as failed
        $remainingIds = $subscribers->skip(1)->pluck('id');
        foreach ($remainingIds as $id) {
            $pivot = $campaign->subscribers()->where('subscriber_id', $id)->first()->pivot;
            $this->assertEquals(CampaignSubscriberStatus::Failed, $pivot->status);
            $this->assertStringContainsString('Queue timeout', $pivot->failed_reason);
        }
    }
}
