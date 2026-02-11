<?php

namespace App\Repositories;

use Closure;
use Illuminate\Support\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

use App\Models\Campaign;
use App\Enums\CampaignStatus;
use App\Models\CampaignSubscriber;
use App\Enums\CampaignSubscriberStatus;
use App\Contracts\Repositories\CampaignRepositoryInterface;


class EloquentCampaignRepository implements CampaignRepositoryInterface
{
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return Campaign::latest()
            ->withCount([
                'subscribers as total_recipients',
                'subscribers as total_sent' => fn($q) => $q->whereRaw('campaign_subscriber.status = ?', [CampaignSubscriberStatus::Sent->value]),
                'subscribers as total_failed' => fn($q) => $q->whereRaw('campaign_subscriber.status = ?', [CampaignSubscriberStatus::Failed->value]),
            ])
            ->paginate($perPage);
    }

    public function findOrFail(int $id): Campaign
    {
        return Campaign::findOrFail($id);
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

    public function update(int $id, array $data): Campaign
    {
        $campaign = $this->findOrFail($id);
        $campaign->update($data);

        return $campaign->refresh();
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

    public function updateStatus(int $campaignId, CampaignStatus $status): void
    {
        $campaign = $this->findOrFail($campaignId);
        $campaign->update(['status' => $status]);
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
}
