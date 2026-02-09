<?php

namespace App\Contracts;

use App\Models\Campaign;
use Illuminate\Support\Collection;

interface CampaignRepositoryInterface
{
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
     * Mark a campaign as sending.
     */
    public function markAsSending(int $id): Campaign;
}
