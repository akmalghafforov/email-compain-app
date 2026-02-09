<?php

namespace Database\Seeders;

use App\Models\Campaign;
use App\Models\Template;
use App\Models\Subscriber;
use Illuminate\Database\Seeder;

class TestDataSeeder extends Seeder
{
    public function run(): void
    {
        // Templates — one per engine
        $bladeTemplate = Template::factory()->blade()->create([
            'name' => 'Weekly Newsletter',
            'subject_template' => 'Your Weekly Update, {{ name }}',
            'body_content' => '<h1>Hello {{ name }}</h1><p>Here is your weekly newsletter.</p>',
        ]);

        $markdownTemplate = Template::factory()->markdown()->create([
            'name' => 'Product Launch',
            'subject_template' => 'Introducing Our New Product',
            'body_content' => "# Exciting News, {{ name }}!\n\nWe just launched something amazing.",
        ]);

        Template::factory()->twig()->create([
            'name' => 'Welcome Email',
            'subject_template' => 'Welcome aboard, {{ name }}!',
            'body_content' => '<h1>Welcome, {{ name }}!</h1><p>Thanks for joining us.</p>',
        ]);

        Template::factory()->mjml()->create([
            'name' => 'Promotional Offer',
            'subject_template' => 'Special Offer Just For You',
        ]);

        // Subscribers — mix of types and statuses
        $regularSubscribers = Subscriber::factory()
            ->count(20)
            ->create();

        $premiumSubscribers = Subscriber::factory()
            ->premium()
            ->count(5)
            ->create();

        $adminSubscribers = Subscriber::factory()
            ->admin()
            ->count(2)
            ->create();

        Subscriber::factory()
            ->unsubscribed()
            ->count(3)
            ->create();

        Subscriber::factory()
            ->bounced()
            ->count(2)
            ->create();

        // Campaigns
        $draftCampaign = Campaign::factory()->create([
            'name' => 'March Newsletter',
            'template_id' => $bladeTemplate->id,
            'sender_channel' => 'smtp',
        ]);

        $scheduledCampaign = Campaign::factory()->scheduled()->create([
            'name' => 'Product Launch Blast',
            'template_id' => $markdownTemplate->id,
            'sender_channel' => 'sendgrid',
        ]);

        $sentCampaign = Campaign::factory()->sent()->create([
            'name' => 'February Newsletter',
            'template_id' => $bladeTemplate->id,
            'sender_channel' => 'smtp',
        ]);

        // Attach subscribers to campaigns via pivot
        $activeSubscribers = $regularSubscribers
            ->merge($premiumSubscribers)
            ->merge($adminSubscribers);

        $draftCampaign->subscribers()->attach(
            $activeSubscribers->pluck('id'),
            ['status' => 'queued'],
        );

        $scheduledCampaign->subscribers()->attach(
            $activeSubscribers->take(10)->pluck('id'),
            ['status' => 'queued'],
        );

        $sentCampaign->subscribers()->attach(
            $activeSubscribers->pluck('id')->mapWithKeys(fn ($id) => [
                $id => [
                    'status' => fake()->randomElement(['sent', 'delivered', 'opened', 'clicked']),
                    'sent_at' => now()->subDays(rand(1, 7)),
                    'opened_at' => fake()->boolean(60) ? now()->subDays(rand(0, 5)) : null,
                    'clicked_at' => fake()->boolean(30) ? now()->subDays(rand(0, 3)) : null,
                ],
            ]),
        );
    }
}
