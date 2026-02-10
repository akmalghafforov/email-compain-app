<?php

declare(strict_types=1);

namespace App\Services\Delivery;

use App\DTOs\SendResult;
use App\Models\Campaign;
use App\Models\Subscriber;
use App\DTOs\CampaignStats;
use App\Models\DeliveryLog;
use App\Contracts\Subscriber\Sendable;
use App\Contracts\DeliveryTrackerInterface;

class DeliveryTracker implements DeliveryTrackerInterface
{
    public function recordSent(Campaign $campaign, Sendable $subscriber, SendResult $result): void
    {
        $subscriberId = $this->resolveSubscriberId($subscriber);

        $campaign->subscribers()->updateExistingPivot($subscriberId, [
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        DeliveryLog::create([
            'campaign_id' => $campaign->id,
            'subscriber_id' => $subscriberId,
            'channel' => $campaign->sender_channel,
            'event' => 'sent',
            'payload' => ['message_id' => $result->messageId],
            'occurred_at' => now(),
        ]);
    }

    public function recordFailed(Campaign $campaign, Sendable $subscriber, string $reason): void
    {
        $subscriberId = $this->resolveSubscriberId($subscriber);

        $campaign->subscribers()->updateExistingPivot($subscriberId, [
            'status' => 'failed',
            'failed_reason' => $reason,
        ]);

        DeliveryLog::create([
            'campaign_id' => $campaign->id,
            'subscriber_id' => $subscriberId,
            'channel' => $campaign->sender_channel,
            'event' => 'failed',
            'payload' => ['error' => $reason],
            'occurred_at' => now(),
        ]);
    }

    public function recordOpen(string $trackingId): void
    {
        // TODO: Implement when tracking pixel endpoint is added
    }

    public function recordClick(string $trackingId, string $url): void
    {
        // TODO: Implement when click tracking endpoint is added
    }

    public function recordBounce(string $messageId, array $payload): void
    {
        // TODO: Implement when webhook processing is added
    }

    public function getStats(Campaign $campaign): CampaignStats
    {
        $totalRecipients = $campaign->subscribers()->count();

        $counts = DeliveryLog::where('campaign_id', $campaign->id)
            ->selectRaw("
                count(*) filter (where event = 'sent') as sent,
                count(*) filter (where event = 'opened') as opened,
                count(*) filter (where event = 'clicked') as clicked,
                count(*) filter (where event = 'bounced') as bounced,
                count(*) filter (where event = 'failed') as failed
            ")
            ->first();

        $totalSent = (int) $counts->sent;

        return new CampaignStats(
            totalRecipients: $totalRecipients,
            totalSent: $totalSent,
            totalOpened: (int) $counts->opened,
            totalClicked: (int) $counts->clicked,
            totalBounced: (int) $counts->bounced,
            totalFailed: (int) $counts->failed,
            openRate: $totalSent > 0 ? round((int) $counts->opened / $totalSent, 4) : 0.0,
            clickRate: $totalSent > 0 ? round((int) $counts->clicked / $totalSent, 4) : 0.0,
            bounceRate: $totalSent > 0 ? round((int) $counts->bounced / $totalSent, 4) : 0.0,
        );
    }

    private function resolveSubscriberId(Sendable $subscriber): int
    {
        if ($subscriber instanceof Subscriber) {
            return $subscriber->id;
        }

        return Subscriber::where('email', $subscriber->getEmail())->firstOrFail()->id;
    }
}
