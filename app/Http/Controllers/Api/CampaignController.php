<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
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

    public function store(Request $request): JsonResponse
    {
        $campaign = $this->campaignRepository->create($request->all());

        return ApiResponse::created($campaign, 'Campaign created successfully.');
    }

    public function show(string $id): JsonResponse
    {
        $campaign = $this->campaignRepository->findOrFail((int) $id);

        return ApiResponse::success($campaign);
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
