<?php

namespace Database\Seeders;

use App\Models\Campaign;
use App\Models\Template;
use App\Models\Subscriber;

use Illuminate\Support\Carbon;
use Illuminate\Database\Seeder;

class TestDataSeeder extends Seeder
{
    public function run(): void
    {
        $templates = $this->seedTemplates();
        $subscribers = $this->seedSubscribers();
        $this->seedCampaignsWithData($templates, $subscribers);
    }

    private function seedTemplates(): array
    {
        $definitions = [
            [
                'name' => 'Flash Sale Announcement',
                'engine' => 'blade',
                'subject_template' => 'Flash Sale: Up to 50% Off Everything!',
                'body_content' => '<h1>Flash Sale is LIVE!</h1><p>Hi {{ $name }},</p><p>For the next 24 hours only, enjoy up to 50% off across our entire store.</p><p>This offer is exclusive to <strong>{{ $email }}</strong>.</p><p>Don\'t wait — deals this good won\'t last!</p><a href="#">Shop Now</a>',
            ],
            [
                'name' => 'Product Launch',
                'engine' => 'blade',
                'subject_template' => 'Our New Product — Now Available',
                'body_content' => '<h1>Something New Just Landed</h1><p>Hi {{ $name }},</p><p>We\'ve been working on something exciting, and today we\'re proud to share it with you.</p><p>As a subscriber at <strong>{{ $email }}</strong>, you get early access.</p><a href="#">Learn More</a>',
            ],
            [
                'name' => 'Account Verification',
                'engine' => 'markdown',
                'subject_template' => 'Verify your email address',
                'body_content' => "# Email Verification\n\nHi {{name}},\n\nPlease verify that **{{email}}** is your email address by clicking the link below:\n\n[Verify My Email](#)\n\nThis link will expire in 24 hours.\n\nIf you didn't create an account, you can safely ignore this email.",
            ],
            [
                'name' => 'Monthly Digest',
                'engine' => 'markdown',
                'subject_template' => 'Your Monthly Digest',
                'body_content' => "# Your Monthly Recap\n\nHi {{name}},\n\nHere's a summary of your activity this month. This digest is sent to **{{email}}**.\n\nCheck your dashboard for full details on your recent orders, loyalty points, and personalized recommendations.\n\nThanks for being a valued customer!",
            ],
        ];

        $templates = [];
        $now = Carbon::now();

        foreach ($definitions as $def) {
            $templates[] = Template::create(array_merge($def, [
                'created_at' => $now,
                'updated_at' => $now,
            ]));
        }

        return $templates;
    }

    private function seedSubscribers(): \Illuminate\Support\Collection
    {
        $companies = ['Acme Corp', 'Globex Inc', 'Initech', 'Umbrella Ltd', 'Stark Industries', 'Wayne Enterprises', 'Cyberdyne Systems', 'Soylent Corp'];
        $cities = ['New York', 'San Francisco', 'London', 'Berlin', 'Tokyo', 'Sydney', 'Toronto', 'Paris'];

        $active = Subscriber::factory(170)
            ->sequence(fn ($sequence) => $sequence->index % 3 === 0
                ? ['metadata' => ['company' => $companies[array_rand($companies)], 'city' => $cities[array_rand($cities)]]]
                : []
            )
            ->create();

        $unsubscribed = Subscriber::factory(20)->unsubscribed()->create();

        $pending = Subscriber::factory(10)->state(['status' => 'pending'])->create();

        return collect()->merge($active)->merge($unsubscribed)->merge($pending);
    }

    private function seedCampaignsWithData(array $templates, \Illuminate\Support\Collection $subscribers): void
    {
        Campaign::create([
            'name' => 'Summer Collection Preview',
            'subject' => 'Sneak Peek: Summer Collection Coming Soon',
            'template_id' => $templates[3]->id, // Product Launch
            'sender_channel' => 'smtp',
            'status' => 'draft',
        ]);

        Campaign::create([
            'name' => 'Customer Feedback Survey',
            'subject' => 'We Value Your Opinion — Take Our Quick Survey',
            'template_id' => $templates[1]->id, // Weekly Newsletter
            'sender_channel' => 'sendgrid',
            'status' => 'draft',
        ]);
    }
}
