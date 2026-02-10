<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

use App\Models\Subscriber;
use App\Enums\SubscriberStatus;

class SubscriberControllerTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    public function test_index_returns_all_subscribers()
    {
        Subscriber::factory()->count(3)->create();

        $response = $this->getJson(route('subscribers.index'));

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_store_creates_new_subscriber()
    {
        $data = [
            'email' => $this->faker->unique()->safeEmail,
            'name' => $this->faker->name,
            'status' => SubscriberStatus::Active->value,
            'metadata' => ['source' => 'test'],
        ];

        $response = $this->postJson(route('subscribers.store'), $data);

        $response->assertCreated()
            ->assertJsonFragment(['email' => $data['email']]);

        $this->assertDatabaseHas('subscribers', ['email' => $data['email']]);
    }

    public function test_store_validates_input()
    {
        $response = $this->postJson(route('subscribers.store'), []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_show_returns_subscriber()
    {
        $subscriber = Subscriber::factory()->create();

        $response = $this->getJson(route('subscribers.show', $subscriber->id));

        $response->assertOk()
            ->assertJsonFragment(['id' => $subscriber->id]);
    }

    public function test_show_returns_404_if_not_found()
    {
        $response = $this->getJson(route('subscribers.show', 999));

        $response->assertNotFound();
    }

    public function test_update_updates_subscriber()
    {
        $subscriber = Subscriber::factory()->create();
        $newData = [
            'name' => 'Updated Name',
            'status' => SubscriberStatus::Pending->value,
        ];

        $response = $this->putJson(route('subscribers.update', $subscriber->id), $newData);

        $response->assertOk()
            ->assertJsonFragment(['name' => 'Updated Name']);

        $this->assertDatabaseHas('subscribers', [
            'id' => $subscriber->id,
            'name' => 'Updated Name',
            'status' => SubscriberStatus::Pending->value,
        ]);
    }

    public function test_destroy_deletes_subscriber()
    {
        $subscriber = Subscriber::factory()->create();

        $response = $this->deleteJson(route('subscribers.destroy', $subscriber->id));

        $response->assertOk();

        $this->assertDatabaseMissing('subscribers', ['id' => $subscriber->id]);
    }

    public function test_update_fails_on_duplicate_email()
    {
        $subscriber1 = Subscriber::factory()->create(['email' => 'user1@example.com']);
        $subscriber2 = Subscriber::factory()->create(['email' => 'user2@example.com']);

        $response = $this->putJson(route('subscribers.update', $subscriber1->id), [
            'email' => 'user2@example.com',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }
}
