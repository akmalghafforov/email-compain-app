<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

use App\Models\Campaign;
use App\Enums\CampaignStatus;
use App\Contracts\CampaignRepositoryInterface;
use App\Contracts\SubscriberRepositoryInterface;

class CollectCampaignSubscribersCommand extends Command
{
    protected $signature = 'campaigns:collect-subscribers {--tries=3 : Maximum attempts per campaign}';

    protected $description = 'Collect active subscribers for all started campaigns';

    public function handle(
        CampaignRepositoryInterface $campaignRepository,
        SubscriberRepositoryInterface $subscriberRepository,
    ): int {
        $campaigns = $campaignRepository->findByStatus(CampaignStatus::Started->value);
        $maxTries = (int) $this->option('tries');

        foreach ($campaigns as $campaign) {
            $lock = Cache::lock("campaign:{$campaign->id}:collecting-subscribers", 10 * 60);

            if (! $lock->get()) {
                $this->info("Campaign #{$campaign->id} is already being processed, skipping.");

                continue;
            }

            try {
                $campaign->update(['status' => CampaignStatus::CollectingSubscribers]);

                $succeeded = false;

                for ($attempt = 1; $attempt <= $maxTries; $attempt++) {
                    try {
                        $this->collectSubscribers($campaign, $subscriberRepository);
                        $succeeded = true;
                        break;
                    } catch (\Throwable $e) {
                        $this->warn("Campaign #{$campaign->id} attempt {$attempt}/{$maxTries} failed: {$e->getMessage()}");
                    }
                }

                if (! $succeeded) {
                    $campaign->update(['status' => CampaignStatus::Failed]);
                    $this->error("Campaign #{$campaign->id} failed after {$maxTries} attempts.");
                }
            } finally {
                $lock->release();
            }
        }

        return self::SUCCESS;
    }

    private function collectSubscribers(
        Campaign $campaign,
        SubscriberRepositoryInterface $subscriberRepository,
    ): void {
        $query = $subscriberRepository->segmentByQuery([
            'status' => 'active',
            'unsubscribed_at' => null,
        ])->whereDoesntHave('campaigns', fn ($q) => $q->where('campaigns.id', $campaign->id));

        $remaining = $query->count();

        if ($remaining === 0) {
            if ($campaign->subscribers()->count() === 0) {
                $campaign->update(['status' => CampaignStatus::Failed]);

                return;
            }

            $campaign->update(['status' => CampaignStatus::SubscribersCollected]);
            $this->info("{$campaign->subscribers()->count()} subscribers collected");

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

        $total = $campaign->subscribers()->count();

        $campaign->update(['status' => CampaignStatus::SubscribersCollected]);

        $this->info("{$total} subscribers collected");
    }
}
