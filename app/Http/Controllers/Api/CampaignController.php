<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

use App\Http\Controllers\Controller;
use App\Contracts\CampaignRepositoryInterface;
use App\Contracts\DeliveryTrackerInterface;

class CampaignController extends Controller
{
    public function __construct(
        private readonly CampaignRepositoryInterface $campaignRepository,
        private readonly DeliveryTrackerInterface $deliveryTracker,
    ) {}

    public function store(Request $request): JsonResponse
    {
        $campaign = $this->campaignRepository->create($request->all());

        return response()->json($campaign, 201);
    }

    public function show(string $id): JsonResponse
    {
        $campaign = $this->campaignRepository->findOrFail((int) $id);

        return response()->json($campaign);
    }

    public function dispatch(string $id): JsonResponse
    {
        $campaign = $this->campaignRepository->markAsStarted((int) $id);

        return response()->json($campaign);
    }

    public function stats(string $id): JsonResponse
    {
        $stats = $this->deliveryTracker->getStats((int) $id);

        return response()->json($stats);
    }
}
