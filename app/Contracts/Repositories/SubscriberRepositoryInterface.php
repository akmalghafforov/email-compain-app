<?php

namespace App\Contracts\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

use App\Models\Subscriber;

interface SubscriberRepositoryInterface
{
    /**
     * Get a query builder for segmented subscribers.
     *
     * @param array<string, mixed> $criteria
     * @return Builder<Subscriber>
     */
    public function segmentByQuery(array $criteria): Builder;

    /**
     * Get paginated subscribers.
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator;
}
