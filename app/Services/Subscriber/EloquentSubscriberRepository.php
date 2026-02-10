<?php

namespace App\Services\Subscriber;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

use App\Models\Campaign;
use App\Models\Subscriber;
use App\Contracts\SubscriberRepositoryInterface;

use App\Enums\SubscriberStatus;

class EloquentSubscriberRepository implements SubscriberRepositoryInterface
{
    /**
     * Find all active subscribers for a given campaign.
     *
     * @return Collection<int, Subscriber>
     */
    public function findActiveForCampaign(int $campaignId): Collection
    {
        $campaign = Campaign::findOrFail($campaignId);

        return $campaign->subscribers()
            ->wherePivot('status', SubscriberStatus::Active->value)
            ->where('subscribers.status', SubscriberStatus::Active->value)
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
        return $this->segmentByQuery($criteria)->get();
    }

    /**
     * Get a query builder for segmented subscribers.
     *
     * @param array<string, mixed> $criteria
     * @return Builder<Subscriber>
     */
    public function segmentByQuery(array $criteria): Builder
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

        return $query;
    }
}
