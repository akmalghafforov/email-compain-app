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
                'name' => 'Welcome Email',
                'engine' => 'twig',
                'subject_template' => 'Welcome to {{ company }}, {{ name }}!',
                'body_content' => '<h1>Welcome, {{ name }}!</h1><p>We\'re thrilled to have you join {{ company }}. Your account is all set up and ready to go.</p><p>Here are a few things you can do to get started:</p><ul><li>Complete your profile</li><li>Browse our latest offerings</li><li>Connect with our community</li></ul><p>If you have any questions, reply to this email — we\'re here to help.</p><p>Cheers,<br>The {{ company }} Team</p>',
            ],
            [
                'name' => 'Weekly Newsletter',
                'engine' => 'twig',
                'subject_template' => '{{ company }} Weekly: {{ headline }}',
                'body_content' => '<h1>{{ headline }}</h1><p>Hi {{ name }},</p><p>Here\'s what happened this week at {{ company }}:</p><h2>Top Stories</h2><p>{{ story_1 }}</p><p>{{ story_2 }}</p><h2>Upcoming Events</h2><p>{{ events }}</p><p>See you next week!</p>',
            ],
            [
                'name' => 'Flash Sale Announcement',
                'engine' => 'blade',
                'subject_template' => 'Flash Sale: Up to {{ $discount }}% Off Everything!',
                'body_content' => '<h1>Flash Sale is LIVE!</h1><p>Hi {{ $name }},</p><p>For the next <strong>{{ $hours }} hours only</strong>, enjoy up to <strong>{{ $discount }}% off</strong> across our entire store.</p><p>Use code <strong>{{ $code }}</strong> at checkout.</p><p>Don\'t wait — deals this good won\'t last!</p><a href="{{ $link }}">Shop Now</a>',
            ],
            [
                'name' => 'Product Launch',
                'engine' => 'blade',
                'subject_template' => 'Introducing {{ $product_name }} — Now Available',
                'body_content' => '<h1>Say Hello to {{ $product_name }}</h1><p>Hi {{ $name }},</p><p>We\'ve been working on something exciting, and today we\'re proud to introduce <strong>{{ $product_name }}</strong>.</p><p>{{ $product_description }}</p><p>Early adopters get <strong>{{ $early_discount }}% off</strong> for the first week.</p><a href="{{ $link }}">Learn More</a>',
            ],
            [
                'name' => 'Account Verification',
                'engine' => 'markdown',
                'subject_template' => 'Verify your email address',
                'body_content' => "# Email Verification\n\nHi {{name}},\n\nPlease verify your email address by clicking the link below:\n\n[Verify My Email]({{verification_url}})\n\nThis link will expire in **{{expiry_hours}} hours**.\n\nIf you didn't create an account, you can safely ignore this email.",
            ],
            [
                'name' => 'Monthly Digest',
                'engine' => 'markdown',
                'subject_template' => 'Your {{month}} Monthly Digest',
                'body_content' => "# Your {{month}} Recap\n\nHi {{name}},\n\nHere's a summary of your activity this month:\n\n- **Orders placed:** {{order_count}}\n- **Total spent:** \${{total_spent}}\n- **Loyalty points earned:** {{points}}\n\n## Recommended for You\n\n{{recommendations}}\n\nThanks for being a valued customer!",
            ],
            [
                'name' => 'Holiday Promotion',
                'engine' => 'twig',
                'subject_template' => '{{ holiday }} Special: Exclusive Deals Inside',
                'body_content' => '<h1>{{ holiday }} Sale Event</h1><p>Hi {{ name }},</p><p>Celebrate {{ holiday }} with our biggest discounts of the year!</p><p>Save up to <strong>{{ discount }}%</strong> on select items through {{ end_date }}.</p><ul><li>{{ deal_1 }}</li><li>{{ deal_2 }}</li><li>{{ deal_3 }}</li></ul><a href="{{ link }}">Browse Deals</a><p>Happy {{ holiday }}!</p>',
            ],
            [
                'name' => 'Abandoned Cart Reminder',
                'engine' => 'twig',
                'subject_template' => '{{ name }}, you left something behind!',
                'body_content' => '<h1>Still Thinking It Over?</h1><p>Hi {{ name }},</p><p>You left <strong>{{ item_count }} item(s)</strong> in your cart totaling <strong>${{ cart_total }}</strong>.</p><p>Your cart is saved, but inventory is limited. Complete your purchase before these items sell out.</p><a href="{{ cart_link }}">Return to Cart</a><p>Need help? Just reply to this email.</p>',
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
