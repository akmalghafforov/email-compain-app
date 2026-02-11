<?php

namespace App\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

use App\Models\Subscriber;
use App\Contracts\Repositories\SubscriberRepositoryInterface;

class EloquentSubscriberRepository implements SubscriberRepositoryInterface
{
    /**
     * Get a query builder for segmented subscribers.
     *
     * @param array<string, mixed> $criteria
     * @return Builder<Subscriber>
     */
    public function segmentByQuery(array $criteria): Builder
    {
        $query = Subscriber::query();

        foreach ($criteria as $field => $value) {
            if (is_array($value)) {
                $query->whereIn($field, $value);
            } elseif ($value === null) {
                $query->whereNull($field);
            } else {
                $query->where($field, $value);
            }
        }

        return $query;
    }

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return Subscriber::latest()->paginate($perPage);
    }
}
