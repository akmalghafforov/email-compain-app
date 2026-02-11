<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Http\JsonResponse;

use App\Http\ApiResponse;
use App\Http\Controllers\Controller;
use App\Contracts\DeliveryTrackerInterface;
use App\Contracts\Repositories\CampaignRepositoryInterface;

class CampaignController extends Controller
{
    public function __construct(
        private readonly CampaignRepositoryInterface $campaignRepository,
        private readonly DeliveryTrackerInterface $deliveryTracker,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->query('per_page', 10);
        $campaigns = $this->campaignRepository->paginate($perPage);

        return ApiResponse::paginated($campaigns);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'template_id' => ['required', 'integer', Rule::exists('templates', 'id')],
            'sender_channel' => ['required', 'string', Rule::in(['smtp', 'sendgrid', 'mailgun'])],
            'scheduled_at' => ['nullable', 'date'],
        ]);

        $campaign = $this->campaignRepository->create($validated);

        return ApiResponse::created($campaign, 'Campaign created successfully.');
    }

    public function show(string $id): JsonResponse
    {
        $campaign = $this->campaignRepository->findOrFail((int) $id);

        return ApiResponse::success($campaign);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $campaign = $this->campaignRepository->update((int) $id, $request->all());

        return ApiResponse::success($campaign, 'Campaign updated successfully.');
    }

    public function dispatch(string $id): JsonResponse
    {
        $campaign = $this->campaignRepository->markAsStarted((int) $id);

        return ApiResponse::success($campaign, 'Campaign dispatched successfully.');
    }

    public function stats(string $id): JsonResponse
    {
        $stats = $this->deliveryTracker->getStats((int) $id);

        return ApiResponse::success($stats);
    }
}
