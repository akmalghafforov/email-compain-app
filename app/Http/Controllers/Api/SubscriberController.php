<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

use App\Http\ApiResponse;
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

}
