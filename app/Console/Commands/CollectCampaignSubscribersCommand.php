<?php

namespace App\Console\Commands;

use App\Contracts\CampaignRepositoryInterface;
use App\Contracts\SubscriberRepositoryInterface;
use App\Enums\CampaignStatus;
use Illuminate\Console\Command;

class CollectCampaignSubscribersCommand extends Command
{
    protected $signature = 'campaigns:collect-subscribers';

    protected $description = 'Collect active subscribers for all started campaigns';

    public function handle(
        CampaignRepositoryInterface $campaignRepository,
        SubscriberRepositoryInterface $subscriberRepository,
    ): int {
        $campaigns = $campaignRepository->findByStatus(CampaignStatus::Started->value);

        foreach ($campaigns as $campaign) {
            $campaign->update(['status' => CampaignStatus::CollectingSubscribers]);

            $subscribers = $subscriberRepository->segmentBy([
                'status' => 'active',
                'unsubscribed_at' => null,
            ]);

            if ($subscribers->isEmpty()) {
                $campaign->update(['status' => CampaignStatus::Failed]);
                continue;
            }

            $pivotData = $subscribers->mapWithKeys(fn ($subscriber) => [
                $subscriber->id => ['status' => 'pending'],
            ])->all();

            $campaign->subscribers()->syncWithoutDetaching($pivotData);

            $campaign->update(['status' => CampaignStatus::SubscribersCollected]);

            $this->info("{$subscribers->count()} subscribers collected");
        }

        return self::SUCCESS;
    }
}
