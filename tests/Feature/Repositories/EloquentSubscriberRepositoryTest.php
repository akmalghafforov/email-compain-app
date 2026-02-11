<?php

namespace Tests\Feature\Repositories;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

use App\Models\Subscriber;
use App\Enums\SubscriberStatus;
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

    public function test_segment_by_query_returns_builder_instance(): void
    {
        $result = $this->repository->segmentByQuery([]);

        $this->assertInstanceOf(Builder::class, $result);
    }

    public function test_segment_by_query_filters_by_scalar_value(): void
    {
        Subscriber::factory()->count(2)->create(['status' => SubscriberStatus::Active]);
        Subscriber::factory()->create(['status' => SubscriberStatus::Unsubscribed]);

        $results = $this->repository->segmentByQuery(['status' => SubscriberStatus::Active->value])->get();

        $this->assertCount(2, $results);
        $results->each(fn ($s) => $this->assertEquals(SubscriberStatus::Active, $s->status));
    }

    public function test_segment_by_query_filters_by_array_value(): void
    {
        Subscriber::factory()->create(['email' => 'a@test.com']);
        Subscriber::factory()->create(['email' => 'b@test.com']);
        Subscriber::factory()->create(['email' => 'c@test.com']);

        $results = $this->repository->segmentByQuery(['email' => ['a@test.com', 'b@test.com']])->get();

        $this->assertCount(2, $results);
        $this->assertEqualsCanonicalizing(
            ['a@test.com', 'b@test.com'],
            $results->pluck('email')->all()
        );
    }

    public function test_segment_by_query_filters_by_null_value(): void
    {
        Subscriber::factory()->create(['metadata' => null]);
        Subscriber::factory()->create(['metadata' => ['key' => 'value']]);

        $results = $this->repository->segmentByQuery(['metadata' => null])->get();

        $this->assertCount(1, $results);
        $this->assertNull($results->first()->metadata);
    }

    public function test_segment_by_query_with_multiple_criteria(): void
    {
        Subscriber::factory()->create(['name' => 'John', 'status' => SubscriberStatus::Active]);
        Subscriber::factory()->create(['name' => 'John', 'status' => SubscriberStatus::Unsubscribed]);
        Subscriber::factory()->create(['name' => 'Jane', 'status' => SubscriberStatus::Active]);

        $results = $this->repository->segmentByQuery([
            'name' => 'John',
            'status' => SubscriberStatus::Active->value,
        ])->get();

        $this->assertCount(1, $results);
        $this->assertEquals('John', $results->first()->name);
        $this->assertEquals(SubscriberStatus::Active, $results->first()->status);
    }

    public function test_segment_by_query_with_empty_criteria_returns_all(): void
    {
        Subscriber::factory()->count(3)->create();

        $results = $this->repository->segmentByQuery([])->get();

        $this->assertCount(3, $results);
    }

    public function test_paginate_returns_paginated_subscribers(): void
    {
        Subscriber::factory()->count(20)->create();

        $result = $this->repository->paginate(10);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertCount(10, $result->items());
        $this->assertEquals(20, $result->total());
    }
}
