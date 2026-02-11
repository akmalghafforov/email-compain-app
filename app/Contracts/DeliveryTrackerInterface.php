<?php

declare(strict_types=1);

namespace App\Contracts;

use App\DTOs\SendResult;
use App\Models\Campaign;
use App\DTOs\CampaignStats;
use App\Contracts\Subscriber\Sendable;

interface DeliveryTrackerInterface
{
    /**
     * Record that an email was sent to a subscriber.
     */
    public function recordSent(Campaign $campaign, Sendable $subscriber, SendResult $result): void;

    /**
     * Record that an email failed to send.
     */
    public function recordFailed(Campaign $campaign, Sendable $subscriber, string $reason): void;

    /**
     * Record that an email was opened via tracking pixel.
     */
    public function recordOpen(string $trackingId): void;

    /**
     * Record that a link in an email was clicked.
     */
    public function recordClick(string $trackingId, string $url): void;

    /**
     * Get aggregated statistics for a campaign.
     *
     * @throws \Throwable If stats cannot be computed
     */
    public function getStats(int $campaignId): CampaignStats;
}
