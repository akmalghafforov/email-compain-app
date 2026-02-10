<?php

declare(strict_types=1);

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldQueue;

use App\Models\Campaign;
use App\Models\Subscriber;
use App\Contracts\EmailSenderInterface;
use App\Contracts\DeliveryTrackerInterface;
use App\Services\Template\TemplateRenderer;
use App\Contracts\Repositories\CampaignRepositoryInterface;

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

    public function handle(
        EmailSenderInterface $emailSender,
        TemplateRenderer $templateRenderer,
        DeliveryTrackerInterface $deliveryTracker,
    ): void {
        $campaign = $this->campaign->loadMissing('template');
        $template = $campaign->template;
        $subscribers = Subscriber::whereIn('id', $this->subscriberIds)->get();

        foreach ($subscribers as $subscriber) {
            $this->sendToSubscriber($subscriber, $campaign, $template, $emailSender, $templateRenderer, $deliveryTracker);
        }
    }

    private function sendToSubscriber(
        Subscriber $subscriber,
        Campaign $campaign,
        $template,
        EmailSenderInterface $emailSender,
        TemplateRenderer $templateRenderer,
        DeliveryTrackerInterface $deliveryTracker,
    ): void {
        try {
            $renderedBody = $templateRenderer->render($template, [
                'name' => $subscriber->getName(),
                'email' => $subscriber->getEmail(),
            ]);

            $result = $emailSender->send($subscriber, $campaign->subject, $renderedBody);

            $deliveryTracker->recordSent($campaign, $subscriber, $result);
        } catch (\Throwable $e) {
            $deliveryTracker->recordFailed($campaign, $subscriber, $e->getMessage());

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
            ->markPendingSubscribersAsFailed($campaign->id, $this->subscriberIds, $reason);

        Log::error("SendCampaignEmailJob failed for campaign #{$campaign->id}", [
            'subscriber_ids' => $this->subscriberIds,
            'error' => $exception?->getMessage(),
        ]);
    }
}
