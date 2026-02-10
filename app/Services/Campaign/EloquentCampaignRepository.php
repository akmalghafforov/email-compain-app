<?php

declare(strict_types=1);

namespace App\Services\Campaign;

use Closure;
use Illuminate\Support\Collection;

use App\Models\Campaign;
use App\Enums\CampaignStatus;
use App\Models\CampaignSubscriber;
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

    public function markAsStarted(int $id): Campaign
    {
        $campaign = $this->findOrFail($id);
        $campaign->update(['status' => CampaignStatus::Started]);

        return $campaign;
    }

    public function chunkPendingSubscribers(Campaign $campaign, int $chunkSize, Closure $callback): void
    {
        CampaignSubscriber::query()
            ->where('campaign_id', $campaign->id)
            ->where('status', 'pending')
            ->select('id', 'subscriber_id')
            ->chunkById($chunkSize, function ($pivotRecords) use ($callback) {
                $callback($pivotRecords->pluck('subscriber_id')->all());
            });
    }

    public function markPendingSubscribersAsFailed(Campaign $campaign, array $subscriberIds, string $reason): void
    {
        CampaignSubscriber::query()
            ->where('campaign_id', $campaign->id)
            ->whereIn('subscriber_id', $subscriberIds)
            ->where('status', 'pending')
            ->update([
                'status' => 'failed',
                'failed_reason' => $reason,
            ]);
    }

    public function updateStatus(Campaign $campaign, CampaignStatus $status): void
    {
        $campaign->update(['status' => $status]);
    }
}
