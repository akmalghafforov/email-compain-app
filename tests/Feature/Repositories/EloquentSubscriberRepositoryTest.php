<?php

namespace Tests\Feature\Repositories;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

use App\Models\Campaign;
use App\Models\Subscriber;
use App\Enums\SubscriberStatus;
use App\Enums\CampaignSubscriberStatus;
use App\Repositories\EloquentSubscriberRepository;

class EloquentSubscriberRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private EloquentSubscriberRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new EloquentSubscriberRepository();
    }

    public function test_it_can_create_a_subscriber()
    {
        $data = [
            'email' => 'test@example.com',
            'name' => 'John Doe',
            'status' => SubscriberStatus::Active->value,
        ];

        $subscriber = $this->repository->create($data);

        $this->assertInstanceOf(Subscriber::class, $subscriber);
        $this->assertEquals('test@example.com', $subscriber->email);
        $this->assertEquals('John Doe', $subscriber->name);
        $this->assertEquals(SubscriberStatus::Active, $subscriber->status);
        $this->assertDatabaseHas('subscribers', ['email' => 'test@example.com']);
    }

    public function test_it_can_find_a_subscriber_by_id()
    {
        $subscriber = Subscriber::factory()->create();

        $found = $this->repository->find($subscriber->id);

        $this->assertNotNull($found);
        $this->assertEquals($subscriber->id, $found->id);
    }

    public function test_it_returns_null_if_subscriber_not_found_by_id()
    {
        $found = $this->repository->find(999);

        $this->assertNull($found);
    }


    public function test_it_can_find_a_subscriber_by_email()
    {
        $subscriber = Subscriber::factory()->create(['email' => 'findme@example.com']);

        $found = $this->repository->findByEmail('findme@example.com');

        $this->assertNotNull($found);
        $this->assertEquals($subscriber->id, $found->id);
    }


    public function test_it_can_update_a_subscriber()
    {
        $subscriber = Subscriber::factory()->create(['name' => 'Old Name']);

        $updated = $this->repository->update($subscriber->id, ['name' => 'New Name']);

        $this->assertEquals('New Name', $updated->name);
        $this->assertDatabaseHas('subscribers', ['id' => $subscriber->id, 'name' => 'New Name']);
    }

    public function test_it_throws_exception_if_updating_non_existent_subscriber()
    {
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        $this->repository->update(999, ['name' => 'New Name']);
    }

    public function test_it_can_delete_a_subscriber()
    {
        $subscriber = Subscriber::factory()->create();

        $result = $this->repository->delete($subscriber->id);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('subscribers', ['id' => $subscriber->id]);
    }

    public function test_it_returns_false_if_deleting_non_existent_subscriber()
    {
        $result = $this->repository->delete(999);

        $this->assertFalse($result);
    }

    public function test_it_can_get_all_subscribers()
    {
        Subscriber::factory()->count(3)->create();

        $all = $this->repository->all();

        $this->assertCount(3, $all);
    }

    public function test_it_can_segment_subscribers_by_criteria()
    {
        Subscriber::factory()->create(['status' => SubscriberStatus::Active, 'email' => 'a@example.com']);
        Subscriber::factory()->create(['status' => SubscriberStatus::Unsubscribed, 'email' => 'b@example.com']);
        Subscriber::factory()->create(['status' => SubscriberStatus::Active, 'email' => 'c@example.com']);

        $results = $this->repository->segmentBy(['status' => SubscriberStatus::Active->value]);

        $this->assertCount(2, $results);
        $this->assertEquals('a@example.com', $results[0]->email);
        $this->assertEquals('c@example.com', $results[1]->email);
    }

    public function test_it_can_find_active_subscribers_for_campaign()
    {
        $campaign = Campaign::factory()->create();

        $activeSubscriber = Subscriber::factory()->create(['status' => SubscriberStatus::Active]);
        $unsubscribedSubscriber = Subscriber::factory()->create(['status' => SubscriberStatus::Unsubscribed]);
        $bouncedSubscriber = Subscriber::factory()->create(['status' => SubscriberStatus::Bounced]);

        // This subscriber is active but not attached to campaign
        $otherActiveSubscriber = Subscriber::factory()->create(['status' => SubscriberStatus::Active]);

        $campaign->subscribers()->attach($activeSubscriber, ['status' => CampaignSubscriberStatus::Pending]);
        $campaign->subscribers()->attach($unsubscribedSubscriber, ['status' => CampaignSubscriberStatus::Pending]);
        $campaign->subscribers()->attach($bouncedSubscriber, ['status' => CampaignSubscriberStatus::Pending]);

        // We expect only the active subscriber attached to the campaign
        $results = $this->repository->findActiveForCampaign($campaign->id);


        $this->assertCount(1, $results);
        $this->assertTrue($results->contains($activeSubscriber));
        $this->assertFalse($results->contains($unsubscribedSubscriber));
        $this->assertFalse($results->contains($bouncedSubscriber));
        $this->assertFalse($results->contains($otherActiveSubscriber));
    }
}
