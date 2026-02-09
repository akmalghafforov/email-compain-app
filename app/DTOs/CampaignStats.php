<?php

declare(strict_types=1);

namespace App\DTOs;

final readonly class CampaignStats
{
    public function __construct(
        public int $totalRecipients,
        public int $totalSent,
        public int $totalOpened,
        public int $totalClicked,
        public int $totalBounced,
        public int $totalFailed,
        public float $openRate,
        public float $clickRate,
        public float $bounceRate,
    ) {}
}
