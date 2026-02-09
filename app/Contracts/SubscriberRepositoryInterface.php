<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\Campaign;
use App\Models\Subscriber;
use Illuminate\Support\Collection;

interface SubscriberRepositoryInterface
{
    /**
     * Find all active subscribers for a given campaign.
     *
     * @return Collection<int, Subscriber>
     */
    public function findActiveForCampaign(Campaign $campaign): Collection;

    /**
     * Find a subscriber by their email address.
     */
    public function findByEmail(string $email): ?Subscriber;

    /**
     * Segment subscribers by the given criteria.
     *
     * @param array<string, mixed> $criteria
     * @return Collection<int, Subscriber>
     */
    public function segmentBy(array $criteria): Collection;
}
