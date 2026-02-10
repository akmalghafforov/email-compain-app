<?php

declare(strict_types=1);

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

use App\Models\Campaign;

class SendCampaignEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param  array<int>  $subscriberIds
     */
    public function __construct(
        public readonly Campaign $campaign,
        public readonly array $subscriberIds,
    ) {}

    public function handle(): void
    {
        // TODO: Implement email sending logic
    }
}
