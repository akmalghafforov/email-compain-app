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

}
