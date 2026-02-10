<?php

namespace App\Console\Commands;

use App\Contracts\CampaignRepositoryInterface;
use App\Contracts\SubscriberRepositoryInterface;
use App\Enums\CampaignStatus;
use App\Models\Campaign;
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
            try {
                $this->collectSubscribers($campaign, $subscriberRepository);
            } catch (\Throwable $e) {
                $campaign->update(['status' => CampaignStatus::Failed]);
                $this->error("Campaign #{$campaign->id} failed: {$e->getMessage()}");
            }
        }

        return self::SUCCESS;
    }

    private function collectSubscribers(
        Campaign $campaign,
        SubscriberRepositoryInterface $subscriberRepository,
    ): void {
        $campaign->update(['status' => CampaignStatus::CollectingSubscribers]);

        $query = $subscriberRepository->segmentByQuery([
            'status' => 'active',
            'unsubscribed_at' => null,
        ]);

        if ($query->count() === 0) {
            $campaign->update(['status' => CampaignStatus::Failed]);

            return;
        }

        $collected = 0;

        $query->chunkById(1000, function ($subscribers) use ($campaign, &$collected) {
            $pivotData = $subscribers->mapWithKeys(fn ($subscriber) => [
                $subscriber->id => ['status' => 'pending'],
            ])->all();

            $campaign->subscribers()->syncWithoutDetaching($pivotData);

            $collected += $subscribers->count();
        });

        $campaign->update(['status' => CampaignStatus::SubscribersCollected]);

        $this->info("{$collected} subscribers collected");
    }
}
