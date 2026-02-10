<?php

declare(strict_types=1);

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;

use App\Models\Campaign;
use App\Models\Subscriber;
use App\Models\DeliveryLog;
use App\Contracts\EmailSenderInterface;
use App\Contracts\CampaignRepositoryInterface;
use App\Services\Template\TemplateRenderer;

class SendCampaignEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [10, 60, 300];

    /**
     * @param  array<int>  $subscriberIds
     */
    public function __construct(
        public readonly Campaign $campaign,
        public readonly array $subscriberIds,
    ) {}

    public function handle(EmailSenderInterface $emailSender, TemplateRenderer $templateRenderer): void
    {
        $campaign = $this->campaign->loadMissing('template');
        $template = $campaign->template;
        $subscribers = Subscriber::whereIn('id', $this->subscriberIds)->get();

        foreach ($subscribers as $subscriber) {
            $this->sendToSubscriber($subscriber, $campaign, $template, $emailSender, $templateRenderer);
        }
    }

    private function sendToSubscriber(
        Subscriber $subscriber,
        Campaign $campaign,
        $template,
        EmailSenderInterface $emailSender,
        TemplateRenderer $templateRenderer,
    ): void {
        try {
            $renderedBody = $templateRenderer->render($template, [
                'name' => $subscriber->getName(),
                'email' => $subscriber->getEmail(),
            ]);

            $result = $emailSender->send($subscriber, $campaign->subject, $renderedBody);

            $campaign->subscribers()->updateExistingPivot($subscriber->id, [
                'status' => 'sent',
                'sent_at' => now(),
            ]);

            DeliveryLog::create([
                'campaign_id' => $campaign->id,
                'subscriber_id' => $subscriber->id,
                'channel' => $campaign->sender_channel,
                'event' => 'sent',
                'payload' => ['message_id' => $result->messageId],
                'occurred_at' => now(),
            ]);
        } catch (\Throwable $e) {
            $campaign->subscribers()->updateExistingPivot($subscriber->id, [
                'status' => 'failed',
                'failed_reason' => $e->getMessage(),
            ]);

            DeliveryLog::create([
                'campaign_id' => $campaign->id,
                'subscriber_id' => $subscriber->id,
                'channel' => $campaign->sender_channel,
                'event' => 'failed',
                'payload' => ['error' => $e->getMessage()],
                'occurred_at' => now(),
            ]);

            Log::error("Failed to send email for campaign #{$campaign->id} to subscriber #{$subscriber->id}", [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle a job failure after all retries are exhausted.
     */
    public function failed(?\Throwable $exception): void
    {
        $campaign = $this->campaign;
        $reason = 'Job failed: ' . ($exception?->getMessage() ?? 'Unknown error');

        app(CampaignRepositoryInterface::class)
            ->markPendingSubscribersAsFailed($campaign, $this->subscriberIds, $reason);

        Log::error("SendCampaignEmailJob failed for campaign #{$campaign->id}", [
            'subscriber_ids' => $this->subscriberIds,
            'error' => $exception?->getMessage(),
        ]);
    }
}
