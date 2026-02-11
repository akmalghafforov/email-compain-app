<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Http\JsonResponse;

use App\Http\ApiResponse;
use App\Enums\TemplateEngine;
use App\Http\Controllers\Controller;
use App\Contracts\Repositories\TemplateRepositoryInterface;

class TemplateController extends Controller
{
    public function __construct(
        private readonly TemplateRepositoryInterface $templateRepository
    ) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->query('per_page', 10);
        $templates = $this->templateRepository->paginate($perPage);

        return ApiResponse::paginated($templates);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'engine' => ['required', Rule::enum(TemplateEngine::class)],
            'subject_template' => ['required', 'string'],
            'body_content' => ['required', 'string'],
            'metadata' => ['nullable', 'array'],
        ]);

        $template = $this->templateRepository->create($validated);

        return ApiResponse::created($template, 'Template created successfully.');
    }

    public function show(string $id): JsonResponse
    {
        $template = $this->templateRepository->findOrFail((int) $id);

        return ApiResponse::success($template);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'engine' => ['nullable', Rule::enum(TemplateEngine::class)],
            'subject_template' => ['nullable', 'string'],
            'body_content' => ['nullable', 'string'],
            'metadata' => ['nullable', 'array'],
        ]);

        $template = $this->templateRepository->update((int) $id, $validated);

        return ApiResponse::success($template, 'Template updated successfully.');
    }

    public function destroy(string $id): JsonResponse
    {
        $this->templateRepository->findOrFail((int) $id);
        $this->templateRepository->delete((int) $id);

        return ApiResponse::message('Template deleted successfully.');
    }
}
