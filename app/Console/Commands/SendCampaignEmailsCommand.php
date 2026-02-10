<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Models\Campaign;
use App\Enums\CampaignStatus;
use App\Contracts\CampaignRepositoryInterface;
use App\Jobs\SendCampaignEmailJob;

class SendCampaignEmailsCommand extends Command
{
    protected $signature = 'campaigns:send-emails';

    protected $description = 'Dispatch email-sending jobs for campaigns with collected subscribers';

    public function handle(CampaignRepositoryInterface $campaignRepository): int
    {
        $campaigns = $campaignRepository->findByStatus(CampaignStatus::SubscribersCollected->value);

        if ($campaigns->isEmpty()) {
            $this->info('No campaigns ready to send.');

            return self::SUCCESS;
        }

        foreach ($campaigns as $campaign) {
            $this->processCampaign($campaign, $campaignRepository);
        }

        return self::SUCCESS;
    }

    private function processCampaign(Campaign $campaign, CampaignRepositoryInterface $campaignRepository): void
    {
        $campaign->update(['status' => CampaignStatus::Sending]);

        $dispatched = 0;

        $campaignRepository->chunkPendingSubscribers($campaign, 50, function (array $subscriberIds) use ($campaign, &$dispatched) {
            SendCampaignEmailJob::dispatch($campaign, $subscriberIds);

            $dispatched += count($subscriberIds);
        });

        $this->info("Campaign #{$campaign->id}: {$dispatched} subscribers queued in batches of 50.");
    }
}

