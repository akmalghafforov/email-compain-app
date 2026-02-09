<?php

namespace App\Services\Subscriber;

use Illuminate\Support\Collection;

use App\Models\Campaign;
use App\Models\Subscriber;
use App\Contracts\SubscriberRepositoryInterface;

class EloquentSubscriberRepository implements SubscriberRepositoryInterface
{
    /**
     * Find all active subscribers for a given campaign.
     *
     * @return Collection<int, Subscriber>
     */
    public function findActiveForCampaign(Campaign $campaign): Collection
    {
        return $campaign->subscribers()
            ->wherePivot('status', 'active')
            ->where('subscribers.status', 'active')
            ->whereNull('subscribers.unsubscribed_at')
            ->get();
    }

    /**
     * Find a subscriber by their email address.
     */
    public function findByEmail(string $email): ?Subscriber
    {
        return Subscriber::where('email', $email)->first();
    }

    /**
     * Segment subscribers by the given criteria.
     *
     * @param array<string, mixed> $criteria
     * @return Collection<int, Subscriber>
     */
    public function segmentBy(array $criteria): Collection
    {
        $query = Subscriber::query();

        foreach ($criteria as $field => $value) {
            if (is_array($value)) {
                $query->whereIn($field, $value);
            } elseif ($value === null) {
                $query->whereNull($field);
            } else {
                $query->where($field, $value);
            }
        }

        return $query->get();
    }
}
