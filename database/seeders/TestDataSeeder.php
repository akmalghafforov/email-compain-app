<?php

namespace Database\Seeders;

use App\Models\Campaign;
use App\Models\Subscriber;
use App\Models\Template;
use Illuminate\Database\Seeder;

class TestDataSeeder extends Seeder
{
    public function run(): void
    {
        $templates = Template::factory(5)->create();

        $subscribers = Subscriber::factory(100)->create();

        Campaign::factory(5)
            ->sequence(fn ($sequence) => [
                'template_id' => $templates[$sequence->index]->id,
            ])
            ->create();
    }
}
