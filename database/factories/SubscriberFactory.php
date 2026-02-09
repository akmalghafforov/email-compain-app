<?php

namespace Database\Factories;

use App\Models\Subscriber;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Subscriber>
 */
class SubscriberFactory extends Factory
{
    protected $model = Subscriber::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'email' => fake()->unique()->safeEmail(),
            'name' => fake()->name(),
            'type' => 'regular',
            'status' => 'active',
            'metadata' => null,
            'subscribed_at' => fake()->dateTimeBetween('-1 year', 'now'),
            'unsubscribed_at' => null,
        ];
    }

    public function premium(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'premium',
            'metadata' => [
                'preferred_frequency' => fake()->randomElement(['daily', 'weekly', 'monthly']),
                'preferred_categories' => fake()->randomElements(
                    ['tech', 'science', 'business', 'health', 'sports'],
                    fake()->numberBetween(1, 3),
                ),
            ],
        ]);
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'admin',
        ]);
    }

    public function unsubscribed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'unsubscribed',
            'unsubscribed_at' => fake()->dateTimeBetween('-1 month', 'now'),
        ]);
    }

    public function bounced(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'bounced',
        ]);
    }
}
