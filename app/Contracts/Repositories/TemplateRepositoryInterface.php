<?php

namespace App\Contracts\Repositories;

use Illuminate\Support\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

use App\Models\Template;

interface TemplateRepositoryInterface
{
    /**
     * Get a paginated list of templates.
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator;

    /**
     * Find a template by its ID.
     */
    public function find(int $id): ?Template;

    /**
     * Find a template by its ID or fail.
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findOrFail(int $id): Template;

    /**
     * Get all templates.
     *
     * @return Collection<int, Template>
     */
    public function all(): Collection;

    /**
     * Create a new template.
     *
     * @param array<string, mixed> $data
     */
    public function create(array $data): Template;

    /**
     * Update an existing template.
     *
     * @param array<string, mixed> $data
     */
    public function update(int $id, array $data): Template;

    /**
     * Delete a template.
     */
    public function delete(int $id): bool;
}
