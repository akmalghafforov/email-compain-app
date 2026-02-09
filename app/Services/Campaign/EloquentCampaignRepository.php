<?php

declare(strict_types=1);

namespace App\Services\Campaign;

use Illuminate\Support\Collection;

use App\Models\Campaign;
use App\Contracts\CampaignRepositoryInterface;

class EloquentCampaignRepository implements CampaignRepositoryInterface
{
    public function find(int $id): ?Campaign
    {
        return Campaign::find($id);
    }

    public function findOrFail(int $id): Campaign
    {
        return Campaign::findOrFail($id);
    }

    /**
     * @return Collection<int, Campaign>
     */
    public function all(): Collection
    {
        return Campaign::all();
    }

    public function create(array $data): Campaign
    {
        return Campaign::create($data);
    }

    /**
     * @return Collection<int, Campaign>
     */
    public function findByStatus(string $status): Collection
    {
        return Campaign::where('status', $status)->get();
    }

    public function markAsSending(int $id): Campaign
    {
        $campaign = $this->findOrFail($id);
        $campaign->update(['status' => 'sending']);

        return $campaign;
    }
}
