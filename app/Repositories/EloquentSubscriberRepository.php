<?php

namespace App\Repositories;

use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Builder;

use App\Models\Campaign;
use App\Models\Subscriber;
use App\Enums\SubscriberStatus;
use App\Contracts\Repositories\SubscriberRepositoryInterface;

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

    /**
     * Get all subscribers.
     *
     * @return Collection<int, Subscriber>
     */
    public function all(): Collection
    {
        return Subscriber::all();
    }

    /**
     * Create a new subscriber.
     *
     * @param array<string, mixed> $data
     */
    public function create(array $data): Subscriber
    {
        return Subscriber::create($data);
    }

    /**
     * Update an existing subscriber.
     *
     * @param array<string, mixed> $data
     */
    public function update(int $id, array $data): Subscriber
    {
        $subscriber = $this->find($id);

        if ($subscriber) {
            $subscriber->update($data);
            return $subscriber;
        }

        throw new \Illuminate\Database\Eloquent\ModelNotFoundException("Subscriber with ID {$id} not found.");
    }

    /**
     * Delete a subscriber.
     */
    public function delete(int $id): bool
    {
        $subscriber = $this->find($id);

        if ($subscriber) {
            return $subscriber->delete();
        }

        return false;
    }

    /**
     * Find a subscriber by ID.
     */
    public function find(int $id): ?Subscriber
    {
        return Subscriber::find($id);
    }
}
