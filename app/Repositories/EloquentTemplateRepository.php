<?php

namespace App\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

use App\Models\Template;
use App\Contracts\Repositories\TemplateRepositoryInterface;

class EloquentTemplateRepository implements TemplateRepositoryInterface
{
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return Template::latest()->paginate($perPage);
    }

    public function create(array $data): Template
    {
        return Template::create($data);
    }
}
