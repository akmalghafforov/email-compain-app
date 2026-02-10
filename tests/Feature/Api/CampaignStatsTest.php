<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Campaign;
use App\Contracts\DeliveryTrackerInterface;
use App\DTOs\CampaignStats;
use Mockery;

class CampaignStatsTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_get_campaign_stats(): void
    {
        $campaign = Campaign::factory()->create();
        
        $stats = new CampaignStats(
            totalRecipients: 100,
            totalSent: 80,
            totalOpened: 40,
            totalClicked: 20,
            totalBounced: 5,
            totalFailed: 15,
            openRate: 0.5,
            clickRate: 0.25,
            bounceRate: 0.0625,
        );

        $this->mock(DeliveryTrackerInterface::class, function ($mock) use ($campaign, $stats) {
            $mock->shouldReceive('getStats')
                ->once()
                ->with($campaign->id)
                ->andReturn($stats);
        });

        $response = $this->getJson("/api/campaigns/{$campaign->id}/stats");

        $response->assertStatus(200)
            ->assertJson([
                'totalRecipients' => 100,
                'totalSent' => 80,
                'totalOpened' => 40,
                'totalClicked' => 20,
                'totalBounced' => 5,
                'totalFailed' => 15,
                'openRate' => 0.5,
                'clickRate' => 0.25,
                'bounceRate' => 0.0625,
            ]);
    }

    public function test_returns_404_if_campaign_not_found(): void
    {
        $response = $this->getJson("/api/campaigns/999/stats");

        $response->assertStatus(404);
    }
}
