<?php

namespace App\Contracts\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

use App\Models\Template;

interface TemplateRepositoryInterface
{
    /**
     * Get a paginated list of templates.
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator;

    /**
     * Create a new template.
     *
     * @param array<string, mixed> $data
     */
    public function create(array $data): Template;
}
