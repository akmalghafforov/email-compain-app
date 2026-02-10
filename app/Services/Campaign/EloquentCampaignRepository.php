<?php

declare(strict_types=1);

namespace App\Services\Campaign;

use Closure;
use Illuminate\Support\Collection;

use App\Models\Campaign;
use App\Enums\CampaignStatus;
use App\Models\CampaignSubscriber;
use App\Contracts\CampaignRepositoryInterface;

use App\Enums\CampaignSubscriberStatus;

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

    public function chunkPendingSubscribers(int $campaignId, int $chunkSize, Closure $callback): void
    {
        CampaignSubscriber::query()
            ->where('campaign_id', $campaignId)
            ->where('status', CampaignSubscriberStatus::Pending->value)
            ->select('id', 'subscriber_id')
            ->chunkById($chunkSize, function ($pivotRecords) use ($callback) {
                $callback($pivotRecords->pluck('subscriber_id')->all());
            });
    }

    public function markPendingSubscribersAsFailed(int $campaignId, array $subscriberIds, string $reason): void
    {
        CampaignSubscriber::query()
            ->where('campaign_id', $campaignId)
            ->whereIn('subscriber_id', $subscriberIds)
            ->where('status', CampaignSubscriberStatus::Pending->value)
            ->update([
                'status' => CampaignSubscriberStatus::Failed->value,
                'failed_reason' => $reason,
            ]);
    }

    public function updateStatus(int $campaignId, CampaignStatus $status): void
    {
        $campaign = $this->findOrFail($campaignId);
        $campaign->update(['status' => $status]);
    }
}
