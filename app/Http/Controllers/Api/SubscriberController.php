<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Http\JsonResponse;

use App\Enums\SubscriberStatus;
use App\Http\Controllers\Controller;
use App\Contracts\SubscriberRepositoryInterface;

class SubscriberController extends Controller
{
    public function __construct(
        protected SubscriberRepositoryInterface $subscriberRepository
    ) {}

    public function index(): JsonResponse
    {
        $subscribers = $this->subscriberRepository->all();

        return response()->json([
            'data' => $subscribers,
        ]);
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

        return response()->json([
            'data' => $subscriber,
            'message' => 'Subscriber created successfully.',
        ], 201);
    }

    public function show(string $id): JsonResponse
    {
        $subscriber = $this->subscriberRepository->find((int) $id);

        if (! $subscriber) {
            return response()->json([
                'message' => 'Subscriber not found.',
            ], 404);
        }

        return response()->json([
            'data' => $subscriber,
        ]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $subscriber = $this->subscriberRepository->find((int) $id);

        if (! $subscriber) {
            return response()->json([
                'message' => 'Subscriber not found.',
            ], 404);
        }

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

        return response()->json([
            'data' => $updatedSubscriber,
            'message' => 'Subscriber updated successfully.',
        ]);
    }

    public function destroy(string $id): JsonResponse
    {
        $deleted = $this->subscriberRepository->delete((int) $id);

        if (! $deleted) {
            return response()->json([
                'message' => 'Subscriber not found or could not be deleted.',
            ], 404);
        }

        return response()->json([
            'message' => 'Subscriber deleted successfully.',
        ]);
    }

    public function import(Request $request): JsonResponse
    {
        // TODO: Implement import functionality later or as a separate task
        return response()->json([
            'message' => 'Import functionality not implemented yet.',
        ], 501);
    }
}
