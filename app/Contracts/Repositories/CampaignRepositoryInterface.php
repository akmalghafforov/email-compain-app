<?php

namespace App\Contracts\Repositories;

use Closure;
use Illuminate\Support\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

use App\Models\Campaign;
use App\Enums\CampaignStatus;

interface CampaignRepositoryInterface
{
    /**
     * Get a paginated list of campaigns.
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator;

    /**
     * Find a campaign by its ID.
     */
    public function find(int $id): ?Campaign;

    /**
     * Find a campaign by its ID or fail.
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findOrFail(int $id): Campaign;

    /**
     * Get all campaigns.
     *
     * @return Collection<int, Campaign>
     */
    public function all(): Collection;

    /**
     * Create a new campaign.
     *
     * @param array<string, mixed> $data
     */
    public function create(array $data): Campaign;

    /**
     * Find campaigns by status.
     *
     * @return Collection<int, Campaign>
     */
    public function findByStatus(string $status): Collection;

    /**
     * Mark a campaign as started.
     */
    public function markAsStarted(int $id): Campaign;

    /**
     * Chunk pending subscribers for a campaign.
     *
     * Iterates over campaign_subscriber pivot records with status 'pending'
     * in chunks, passing each chunk's subscriber IDs to the callback.
     *
     * @param  Closure(list<int>): void  $callback
     */
    public function chunkPendingSubscribers(int $campaignId, int $chunkSize, Closure $callback): void;

    /**
     * Mark pending subscribers as failed for a campaign.
     *
     * @param  array<int>  $subscriberIds
     */
    public function markPendingSubscribersAsFailed(int $campaignId, array $subscriberIds, string $reason): void;

    /**
     * Update a campaign's attributes.
     *
     * @param array<string, mixed> $data
     */
    public function update(int $id, array $data): Campaign;

    /**
     * Update the status of a campaign.
     */
    public function updateStatus(int $campaignId, CampaignStatus $status): void;
}
