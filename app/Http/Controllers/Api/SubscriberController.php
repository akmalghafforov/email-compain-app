<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Http\JsonResponse;

use App\Http\ApiResponse;
use App\Enums\SubscriberStatus;
use App\Http\Controllers\Controller;
use App\Contracts\Repositories\SubscriberRepositoryInterface;

class SubscriberController extends Controller
{
    public function __construct(
        protected SubscriberRepositoryInterface $subscriberRepository
    ) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->query('per_page', 10);
        $subscribers = $this->subscriberRepository->paginate($perPage);

        return ApiResponse::paginated($subscribers);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email', 'unique:subscribers,email'],
            'name' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::enum(SubscriberStatus::class)],
            'metadata' => ['nullable', 'array'],
        ]);

        $subscriber = $this->subscriberRepository->create($validated);

        return ApiResponse::created($subscriber, 'Subscriber created successfully.');
    }

    public function show(string $id): JsonResponse
    {
        $subscriber = $this->subscriberRepository->findOrFail((int) $id);

        return ApiResponse::success($subscriber);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $subscriber = $this->subscriberRepository->findOrFail((int) $id);

        $validated = $request->validate([
            'email' => [
                'nullable',
                'string',
                'email',
                Rule::unique('subscribers', 'email')->ignore($subscriber->id),
            ],
            'name' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::enum(SubscriberStatus::class)],
            'metadata' => ['nullable', 'array'],
        ]);

        $updatedSubscriber = $this->subscriberRepository->update($subscriber->id, $validated);

        return ApiResponse::success($updatedSubscriber, 'Subscriber updated successfully.');
    }

    public function destroy(string $id): JsonResponse
    {
        $this->subscriberRepository->findOrFail((int) $id);
        $this->subscriberRepository->delete((int) $id);

        return ApiResponse::message('Subscriber deleted successfully.');
    }

    public function import(Request $request): JsonResponse
    {
        // TODO: Implement import functionality later or as a separate task
        return ApiResponse::error('Import functionality not implemented yet.', 501);
    }
}
