<?php

namespace Database\Factories;

use App\Models\Template;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Template>
 */
class TemplateFactory extends Factory
{
    protected $model = Template::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'engine' => fake()->randomElement(['blade', 'markdown', 'twig', 'mjml']),
            'subject_template' => fake()->sentence(),
            'body_content' => '<h1>Hello {{ name }}</h1><p>' . fake()->paragraph() . '</p>',
            'metadata' => null,
        ];
    }

    public function blade(): static
    {
        return $this->state(fn (array $attributes) => [
            'engine' => 'blade',
        ]);
    }

    public function markdown(): static
    {
        return $this->state(fn (array $attributes) => [
            'engine' => 'markdown',
            'body_content' => "# Hello {{ name }}\n\n" . fake()->paragraph(),
        ]);
    }

    public function twig(): static
    {
        return $this->state(fn (array $attributes) => [
            'engine' => 'twig',
        ]);
    }

    public function mjml(): static
    {
        return $this->state(fn (array $attributes) => [
            'engine' => 'mjml',
        ]);
    }
}
