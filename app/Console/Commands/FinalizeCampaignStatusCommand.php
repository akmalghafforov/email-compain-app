<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Models\Campaign;
use App\Enums\CampaignStatus;
use App\Contracts\DeliveryTrackerInterface;
use App\Contracts\CampaignRepositoryInterface;

class FinalizeCampaignStatusCommand extends Command
{
    protected $signature = 'campaigns:finalize-status';

    protected $description = 'Set final status (sent, partially_sent, failed) for campaigns that finished sending';

    public function handle(
        CampaignRepositoryInterface $campaignRepository,
        DeliveryTrackerInterface $deliveryTracker,
    ): int {
        $campaigns = $campaignRepository->findByStatus(CampaignStatus::Sending->value);

        if ($campaigns->isEmpty()) {
            $this->info('No campaigns in sending status.');

            return self::SUCCESS;
        }

        foreach ($campaigns as $campaign) {
            $this->finalizeCampaign($campaign, $campaignRepository, $deliveryTracker);
        }

        return self::SUCCESS;
    }

    private function finalizeCampaign(
        Campaign $campaign,
        CampaignRepositoryInterface $campaignRepository,
        DeliveryTrackerInterface $deliveryTracker,
    ): void {
        $stats = $deliveryTracker->getStats($campaign->id);

        $pending = $stats->totalRecipients - $stats->totalSent - $stats->totalFailed;

        if ($pending > 0) {
            $this->info("Campaign #{$campaign->id}: still has {$pending} pending subscribers, skipping.");

            return;
        }

        $status = $this->resolveStatus($stats->totalSent, $stats->totalFailed);

        $campaignRepository->updateStatus($campaign->id, $status);

        $this->info("Campaign #{$campaign->id}: finalized as {$status->value} (sent: {$stats->totalSent}, failed: {$stats->totalFailed}).");
    }

    private function resolveStatus(int $sent, int $failed): CampaignStatus
    {
        if ($failed === 0) {
            return CampaignStatus::Sent;
        }

        if ($sent === 0) {
            return CampaignStatus::Failed;
        }

        return CampaignStatus::PartiallySent;
    }
}
