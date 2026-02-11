<?php

namespace App\Repositories;

use Illuminate\Support\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

use App\Models\Template;
use App\Contracts\Repositories\TemplateRepositoryInterface;

class EloquentTemplateRepository implements TemplateRepositoryInterface
{
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return Template::latest()->paginate($perPage);
    }

    public function find(int $id): ?Template
    {
        return Template::find($id);
    }

    public function findOrFail(int $id): Template
    {
        return Template::findOrFail($id);
    }

    /**
     * @return Collection<int, Template>
     */
    public function all(): Collection
    {
        return Template::all();
    }

    public function create(array $data): Template
    {
        return Template::create($data);
    }

    public function update(int $id, array $data): Template
    {
        $template = $this->findOrFail($id);
        $template->update($data);

        return $template->refresh();
    }

    public function delete(int $id): bool
    {
        $template = $this->findOrFail($id);

        return $template->delete();
    }
}
