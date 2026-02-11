<?php

namespace App\DTOs;

final readonly class CampaignStats
{
    public function __construct(
        public int $totalRecipients,
        public int $totalSent,
        public int $totalOpened,
        public int $totalClicked,

        public int $totalFailed,
        public float $openRate,
        public float $clickRate,
    ) {}
}
