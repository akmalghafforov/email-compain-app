<?php

namespace App\Contracts\Repositories;

use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

use App\Models\Subscriber;

interface SubscriberRepositoryInterface
{
    /**
     * Find all active subscribers for a given campaign.
     *
     * @return Collection<int, Subscriber>
     */
    public function findActiveForCampaign(int $campaignId): Collection;

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

    /**
     * Get a query builder for segmented subscribers.
     *
     *
     * @param array<string, mixed> $criteria
     * @return Builder<Subscriber>
     */
    public function segmentByQuery(array $criteria): Builder;

    /**
     * Get all subscribers.
     *
     * @return Collection<int, Subscriber>
     */
    public function all(): Collection;

    /**
     * Get paginated subscribers.
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator;

    /**
     * Create a new subscriber.
     *
     * @param array<string, mixed> $data
     */
    public function create(array $data): Subscriber;

    /**
     * Update an existing subscriber.
     *
     * @param array<string, mixed> $data
     */
    public function update(int $id, array $data): Subscriber;

    /**
     * Delete a subscriber.
     */
    public function delete(int $id): bool;

    /**
     * Find a subscriber by ID.
     */
    public function find(int $id): ?Subscriber;

    /**
     * Find a subscriber by ID or fail.
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findOrFail(int $id): Subscriber;
}
